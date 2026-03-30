#!/usr/bin/env python3
import json
import os
import random
import re
import time
import urllib.parse
import urllib.request
from concurrent.futures import ThreadPoolExecutor, as_completed
from typing import Dict, Iterable, List, Set

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT_PHP = os.path.join(ROOT, "config", "runtime_i18n_en.php")
OUT_JSON = os.path.join(ROOT, "config", "runtime_i18n_en.json")
TARGETS = [
    "views",
    "Controllers",
    "public/js",
    "Models",
    "Services",
    "Core",
    "public/index.php",
]

JP_RE = re.compile(r"[\u3040-\u30ff\u3400-\u4dbf\u4e00-\u9fff]")
QUOTE_SEG_RE = re.compile(r"['\"`]([^'\"`]*[\u3040-\u30ff\u3400-\u4dbf\u4e00-\u9fff][^'\"`]*)['\"`]")
HTML_TEXT_RE = re.compile(r">([^<]*[\u3040-\u30ff\u3400-\u4dbf\u4e00-\u9fff][^<]*)<")

SKIP_LINE_RE = re.compile(
    r"\b("
    r"ALTER|CREATE|INSERT|UPDATE|DELETE|SELECT|FROM|WHERE|TABLE|INDEX|CONSTRAINT|ENGINE|VALUES|"
    r"SHOW\s+CREATE|SET\s+@|DROP\s+TABLE|JOIN|UNION|GROUP\s+BY|ORDER\s+BY"
    r")\b",
    re.IGNORECASE,
)

SKIP_FRAGMENTS = [
    "http://",
    "https://",
    "BASE_PATH",
    "namespace ",
    "function ",
    "class ",
    "use ",
    "PDO::",
    "JSON_",
    "preg_match(",
    "/api/",
    "->prepare(",
    "->execute(",
]

MANUAL_OVERRIDES = {
    "ワークフロー": "Workflow",
    "申請": "Request",
    "承認": "Approval",
    "承認待ち": "Pending approval",
    "差し戻し": "Sent back",
    "却下": "Rejected",
    "日報": "Daily report",
    "組織": "Organization",
    "ユーザー": "User",
    "管理者": "Administrator",
    "バックアップ": "Backup",
}


def contains_jp(text: str) -> bool:
    return bool(JP_RE.search(text))


def normalize(text: str) -> str:
    text = text.replace("\\n", " ").replace("\\r", " ")
    return re.sub(r"\s+", " ", text).strip()


def looks_code(text: str) -> bool:
    if re.search(r"^[\s\-_=+*/(){}\[\];:,.$<>!|&?\\]+$", text):
        return True

    symbol_ratio = len(re.findall(r"[\[\]{}();:=<>$\\]", text)) / max(len(text), 1)
    if symbol_ratio > 0.18:
        return True

    if "=>" in text or "::" in text or "->" in text:
        return True

    return False


def accept_phrase(text: str) -> bool:
    if text == "" or not contains_jp(text):
        return False

    if len(text) > 180:
        return False

    skip_count = sum(1 for f in SKIP_FRAGMENTS if f in text)
    if skip_count >= 2:
        return False

    if looks_code(text):
        return False

    return True


def collect_target_files() -> List[str]:
    files: List[str] = []
    for target in TARGETS:
        abs_target = os.path.join(ROOT, target)
        if os.path.isfile(abs_target):
            files.append(abs_target)
            continue
        if not os.path.isdir(abs_target):
            continue
        for dir_path, _, names in os.walk(abs_target):
            for name in names:
                if name.endswith((".php", ".js", ".json", ".html", ".htm", ".md")):
                    files.append(os.path.join(dir_path, name))
    return files


def extract_from_line(line: str) -> Set[str]:
    results: Set[str] = set()
    if not contains_jp(line):
        return results
    if SKIP_LINE_RE.search(line):
        return results

    s = normalize(line)
    if s == "":
        return results

    for m in QUOTE_SEG_RE.finditer(s):
        part = normalize(m.group(1))
        if accept_phrase(part):
            results.add(part)

    for m in HTML_TEXT_RE.finditer(s):
        part = normalize(m.group(1))
        if accept_phrase(part):
            results.add(part)

    cleaned = re.sub(r"<\?php.*?\?>", " ", s)
    cleaned = re.sub(r"<[^>]+>", " ", cleaned)
    cleaned = re.sub(r"['\"`]", " ", cleaned)
    cleaned = normalize(cleaned)
    if accept_phrase(cleaned):
        results.add(cleaned)

    for part in re.split(r"[。！？!?]", cleaned):
        part = normalize(part)
        if accept_phrase(part):
            results.add(part)

    return results


def extract_candidates(files: Iterable[str]) -> Set[str]:
    candidates: Set[str] = set()
    for path in files:
        try:
            with open(path, "r", encoding="utf-8", errors="ignore") as f:
                for line in f:
                    for phrase in extract_from_line(line):
                        candidates.add(phrase)
        except Exception:
            continue
    for source, translated in MANUAL_OVERRIDES.items():
        candidates.add(source)
    return candidates


def translate_one(text: str, retries: int = 3) -> str:
    if text in MANUAL_OVERRIDES:
        return MANUAL_OVERRIDES[text]

    q = urllib.parse.quote(text)
    url = f"https://translate.googleapis.com/translate_a/single?client=gtx&sl=ja&tl=en&dt=t&q={q}"
    for i in range(retries):
        try:
            with urllib.request.urlopen(url, timeout=15) as r:
                payload = r.read().decode("utf-8", errors="replace")
            data = json.loads(payload)
            translated = ""
            if isinstance(data, list) and data and isinstance(data[0], list):
                for seg in data[0]:
                    if isinstance(seg, list) and seg:
                        translated += str(seg[0])
            translated = normalize(translated)
            if translated != "":
                return translated
        except Exception:
            time.sleep((0.4 * (i + 1)) + random.uniform(0.05, 0.2))
    return text


def php_escape(s: str) -> str:
    return s.replace("\\", "\\\\").replace("'", "\\'")


def load_existing() -> Dict[str, str]:
    if not os.path.exists(OUT_JSON):
        return {}
    try:
        with open(OUT_JSON, "r", encoding="utf-8") as f:
            data = json.load(f)
        if isinstance(data, dict):
            normalized: Dict[str, str] = {}
            for k, v in data.items():
                if isinstance(k, str) and isinstance(v, str):
                    normalized[k] = v
            return normalized
    except Exception:
        pass
    return {}


def write_outputs(translations: Dict[str, str]) -> None:
    items = sorted(translations.items(), key=lambda kv: (-len(kv[0]), kv[0]))

    with open(OUT_JSON, "w", encoding="utf-8") as f:
        json.dump({k: v for k, v in items}, f, ensure_ascii=False, indent=2)

    with open(OUT_PHP, "w", encoding="utf-8") as f:
        f.write("<?php\n\nreturn [\n")
        for k, v in items:
            f.write(f"    '{php_escape(k)}' => '{php_escape(v)}',\n")
        f.write("];\n")


def main() -> None:
    files = collect_target_files()
    print(f"Scanned files: {len(files)}")
    candidates = extract_candidates(files)
    print(f"Collected candidates: {len(candidates)}")

    translations = load_existing()
    print(f"Loaded existing map entries: {len(translations)}")

    phrases = sorted(candidates, key=lambda x: (-len(x), x))
    pending = [p for p in phrases if p not in translations]
    total = len(pending)
    print(f"Pending translation count: {total}")

    if total > 0:
        done = 0
        with ThreadPoolExecutor(max_workers=8) as ex:
            future_map = {ex.submit(translate_one, phrase): phrase for phrase in pending}
            for fut in as_completed(future_map):
                src = future_map[fut]
                try:
                    dst = fut.result()
                except Exception:
                    dst = src
                translations[src] = dst
                done += 1
                if done % 100 == 0 or done == total:
                    print(f"Translated {done}/{total}")
                    write_outputs(translations)
    for source, translated in MANUAL_OVERRIDES.items():
        translations[source] = translated

    write_outputs(translations)
    print(f"Wrote: {OUT_JSON}")
    print(f"Wrote: {OUT_PHP}")
    print(f"Total map entries: {len(translations)}")


if __name__ == "__main__":
    main()
