-- ============================================================
-- Migration: 001_create_users_table.sql
-- Rubrica - Demonstracao do Banco de Dados:
--   1. Estrutura do banco para autenticacao
--   2. Requisitos de seguranca:
--      - password: hash BCrypt (nunca texto puro)
--      - remember_token: para "lembrar de mim" (cookie longo)
--      - last_login_at: timestamp do ultimo login
--      - email_verified_at: confirmacao de e-mail (2FA / verificacao)
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id                BIGSERIAL       PRIMARY KEY,
    full_name         VARCHAR(255)    NOT NULL,
    cpf               VARCHAR(11)     NOT NULL UNIQUE,     -- Validado e unico
    email             VARCHAR(255)    NOT NULL UNIQUE,     -- Usado para autenticacao
    password          VARCHAR(255)    NOT NULL,            -- Hash BCrypt ($2y$12$...)
    phone             VARCHAR(20)     NOT NULL,
    role              VARCHAR(20)     NOT NULL DEFAULT 'student'
                          CHECK (role IN ('student', 'teacher', 'manager')),
    status            VARCHAR(20)     NOT NULL DEFAULT 'active'
                          CHECK (status IN ('active', 'blocked', 'inactive')),

    -- Campos de seguranca adicionais (rubrica item 2)
    remember_token    VARCHAR(100)    NULL,                -- Token para "lembrar de mim"
    last_login_at     TIMESTAMP       NULL,                -- Rastreabilidade de acesso
    email_verified_at TIMESTAMP       NULL,                -- Para verificacao de 2 etapas
    password_reset_token VARCHAR(100) NULL,                -- Token para recuperar senha
    password_reset_expires_at TIMESTAMP NULL,             -- Expiracao do token de reset

    created_at        TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMP       NOT NULL DEFAULT NOW()
);

-- Indices para consultas rapidas de autenticacao
CREATE INDEX IF NOT EXISTS idx_users_email  ON users (email);
CREATE INDEX IF NOT EXISTS idx_users_cpf    ON users (cpf);
CREATE INDEX IF NOT EXISTS idx_users_status ON users (status, role);

-- ============================================================
-- Tabela: plans (Planos da arena)
-- ============================================================
CREATE TABLE IF NOT EXISTS plans (
    id          BIGSERIAL       PRIMARY KEY,
    name        VARCHAR(255)    NOT NULL,
    price       DECIMAL(10,2)   NOT NULL,
    description TEXT            NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP       NOT NULL DEFAULT NOW()
);

-- ============================================================
-- Tabela: invoices (Faturas de mensalidades)
-- ============================================================
CREATE TABLE IF NOT EXISTS invoices (
    id          BIGSERIAL       PRIMARY KEY,
    user_id     BIGINT          NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    amount      DECIMAL(10,2)   NOT NULL,
    due_date    DATE            NOT NULL,
    paid_at     TIMESTAMP       NULL,          -- NULL = nao paga
    status      VARCHAR(20)     NOT NULL DEFAULT 'pending'
                    CHECK (status IN ('pending', 'paid', 'overdue', 'canceled')),
    created_at  TIMESTAMP       NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP       NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_invoices_user_id ON invoices (user_id);
CREATE INDEX IF NOT EXISTS idx_invoices_status  ON invoices (status, due_date);

-- ============================================================
-- Tabela: complaints (Registro de Reclamacoes - Problem Track)
-- ============================================================
CREATE TABLE IF NOT EXISTS complaints (
    id           BIGSERIAL      PRIMARY KEY,
    title        VARCHAR(255)   NOT NULL,
    description  TEXT           NOT NULL,
    author_email VARCHAR(255)   NOT NULL,
    status       VARCHAR(20)    NOT NULL DEFAULT 'open'
                     CHECK (status IN ('open', 'in_progress', 'resolved')),
    created_at   TIMESTAMP      NOT NULL DEFAULT NOW(),
    updated_at   TIMESTAMP      NOT NULL DEFAULT NOW()
);

-- ============================================================
-- Tabela: chat_messages (Chat da Arena)
-- ============================================================
CREATE TABLE IF NOT EXISTS chat_messages (
    id         BIGSERIAL      PRIMARY KEY,
    sender     VARCHAR(255)   NOT NULL,
    message    TEXT           NOT NULL,
    sent_at    TIMESTAMP      NOT NULL DEFAULT NOW()
);

-- ============================================================
-- Seeds iniciais (dados de demonstracao)
-- ============================================================

-- Admin de demonstracao: admin@arena.com / admin123
-- Hash gerado com: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO users (full_name, cpf, email, password, phone, role, status)
VALUES (
    'Administrador Arena',
    '00000000001',
    'admin@arena.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    '41999990001',
    'manager',
    'active'
) ON CONFLICT (email) DO NOTHING;

-- Aluno de demonstracao: aluno@arena.com / aluno123
INSERT INTO users (full_name, cpf, email, password, phone, role, status)
VALUES (
    'Joao Aluno',
    '00000000002',
    'aluno@arena.com',
    '$2y$12$3euPcmQFCiblsZewd1JD4OZBqFCR7m0jXP1MjM8fwKE.Lq6JiVMi',
    '41999990002',
    'student',
    'active'
) ON CONFLICT (email) DO NOTHING;
