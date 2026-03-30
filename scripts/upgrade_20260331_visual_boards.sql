-- Visual Boards feature tables
-- Apply this script for existing installations.

CREATE TABLE IF NOT EXISTS visual_boards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL,
    template_key ENUM('mind_map','flowchart','brainstorm','planning') NOT NULL DEFAULT 'mind_map',
    owner_type ENUM('user','team','organization') NOT NULL,
    owner_id INT NOT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_visual_boards_owner (owner_type, owner_id),
    INDEX idx_visual_boards_updated (updated_at),
    CONSTRAINT fk_visual_boards_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS visual_board_members (
    board_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('admin','editor','viewer') NOT NULL DEFAULT 'viewer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (board_id, user_id),
    INDEX idx_visual_board_members_user (user_id),
    CONSTRAINT fk_visual_board_members_board FOREIGN KEY (board_id) REFERENCES visual_boards(id) ON DELETE CASCADE,
    CONSTRAINT fk_visual_board_members_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS visual_board_nodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL,
    parent_id INT NULL,
    linked_task_id INT NULL,
    node_type ENUM('topic','idea','action','note') NOT NULL DEFAULT 'note',
    title VARCHAR(255) NOT NULL,
    content TEXT NULL,
    x DECIMAL(10,2) NOT NULL DEFAULT 0,
    y DECIMAL(10,2) NOT NULL DEFAULT 0,
    width INT NOT NULL DEFAULT 220,
    height INT NOT NULL DEFAULT 96,
    color VARCHAR(20) NOT NULL DEFAULT '#fff4c2',
    is_collapsed TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_visual_board_nodes_board (board_id, sort_order),
    INDEX idx_visual_board_nodes_parent (parent_id),
    INDEX idx_visual_board_nodes_task (linked_task_id),
    CONSTRAINT fk_visual_board_nodes_board FOREIGN KEY (board_id) REFERENCES visual_boards(id) ON DELETE CASCADE,
    CONSTRAINT fk_visual_board_nodes_parent FOREIGN KEY (parent_id) REFERENCES visual_board_nodes(id) ON DELETE SET NULL,
    CONSTRAINT fk_visual_board_nodes_task FOREIGN KEY (linked_task_id) REFERENCES task_cards(id) ON DELETE SET NULL,
    CONSTRAINT fk_visual_board_nodes_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS visual_board_edges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    board_id INT NOT NULL,
    source_node_id INT NOT NULL,
    target_node_id INT NOT NULL,
    label VARCHAR(255) NULL,
    line_style ENUM('solid','dashed') NOT NULL DEFAULT 'solid',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_visual_board_edges_board (board_id),
    CONSTRAINT fk_visual_board_edges_board FOREIGN KEY (board_id) REFERENCES visual_boards(id) ON DELETE CASCADE,
    CONSTRAINT fk_visual_board_edges_source FOREIGN KEY (source_node_id) REFERENCES visual_board_nodes(id) ON DELETE CASCADE,
    CONSTRAINT fk_visual_board_edges_target FOREIGN KEY (target_node_id) REFERENCES visual_board_nodes(id) ON DELETE CASCADE,
    CONSTRAINT fk_visual_board_edges_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
