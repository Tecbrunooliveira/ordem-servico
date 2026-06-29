-- =============================================================================
-- Gestão Técnica — Schema MySQL (português)
-- =============================================================================
-- Importe este arquivo no phpMyAdmin após criar o banco de dados.
-- ATENÇÃO: os DROP TABLE abaixo apagam todos os dados existentes.
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- -----------------------------------------------------------------------------
-- Remoção (ordem inversa das dependências)
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS `anexos_tarefa`;
DROP TABLE IF EXISTS `comentarios_tarefa`;
DROP TABLE IF EXISTS `tarefas`;
DROP TABLE IF EXISTS `pausas_ordem_servico`;
DROP TABLE IF EXISTS `comentarios_ordem_servico`;
DROP TABLE IF EXISTS `ordens_servico`;
DROP TABLE IF EXISTS `cliente_usuario`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `empresas`;
DROP TABLE IF EXISTS `papel_tem_permissoes`;
DROP TABLE IF EXISTS `modelo_tem_papeis`;
DROP TABLE IF EXISTS `modelo_tem_permissoes`;
DROP TABLE IF EXISTS `papeis`;
DROP TABLE IF EXISTS `permissoes`;
DROP TABLE IF EXISTS `failed_jobs`;
DROP TABLE IF EXISTS `job_batches`;
DROP TABLE IF EXISTS `jobs`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;
DROP TABLE IF EXISTS `sessoes`;
DROP TABLE IF EXISTS `tokens_redefinicao_senha`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `migrations`;

-- -----------------------------------------------------------------------------
-- Controle de migrations (Laravel)
-- -----------------------------------------------------------------------------

CREATE TABLE `migrations` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `migration` varchar(255) NOT NULL,
    `batch` int NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Autenticação e sessão
-- -----------------------------------------------------------------------------

CREATE TABLE `usuarios` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `nome` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `email_verificado_em` timestamp NULL DEFAULT NULL,
    `senha` varchar(255) NOT NULL,
    `telefone` varchar(20) DEFAULT NULL,
    `tipo` varchar(20) NOT NULL DEFAULT 'tecnico' COMMENT 'administrador, tecnico, cliente',
    `ativo` tinyint(1) NOT NULL DEFAULT 1,
    `token_lembrar` varchar(100) DEFAULT NULL,
    `criado_em` timestamp NULL DEFAULT NULL,
    `atualizado_em` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `usuarios_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tokens_redefinicao_senha` (
    `email` varchar(255) NOT NULL,
    `token` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessoes` (
    `id` varchar(255) NOT NULL,
    `user_id` bigint unsigned DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text,
    `payload` longtext NOT NULL,
    `last_activity` int NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sessoes_user_id_index` (`user_id`),
    KEY `sessoes_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Cache e filas (nomes padrão do Laravel)
-- -----------------------------------------------------------------------------

CREATE TABLE `cache` (
    `key` varchar(255) NOT NULL,
    `value` mediumtext NOT NULL,
    `expiration` bigint NOT NULL,
    PRIMARY KEY (`key`),
    KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
    `key` varchar(255) NOT NULL,
    `owner` varchar(255) NOT NULL,
    `expiration` bigint NOT NULL,
    PRIMARY KEY (`key`),
    KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `queue` varchar(255) NOT NULL,
    `payload` longtext NOT NULL,
    `attempts` smallint unsigned NOT NULL,
    `reserved_at` int unsigned DEFAULT NULL,
    `available_at` int unsigned NOT NULL,
    `created_at` int unsigned NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
    `id` varchar(255) NOT NULL,
    `name` varchar(255) NOT NULL,
    `total_jobs` int NOT NULL,
    `pending_jobs` int NOT NULL,
    `failed_jobs` int NOT NULL,
    `failed_job_ids` longtext NOT NULL,
    `options` mediumtext,
    `cancelled_at` int DEFAULT NULL,
    `created_at` int NOT NULL,
    `finished_at` int DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `uuid` varchar(255) NOT NULL,
    `connection` varchar(255) NOT NULL,
    `queue` varchar(255) NOT NULL,
    `payload` longtext NOT NULL,
    `exception` longtext NOT NULL,
    `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
    KEY `failed_jobs_connection_queue_failed_at_index` (`connection`, `queue`, `failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Permissões (Spatie)
-- -----------------------------------------------------------------------------

CREATE TABLE `permissoes` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `guard_name` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `permissoes_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `papeis` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `guard_name` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `papeis_name_guard_name_unique` (`name`, `guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `modelo_tem_permissoes` (
    `permission_id` bigint unsigned NOT NULL,
    `model_type` varchar(255) NOT NULL,
    `model_id` bigint unsigned NOT NULL,
    PRIMARY KEY (`permission_id`, `model_id`, `model_type`),
    KEY `modelo_tem_permissoes_model_id_model_type_index` (`model_id`, `model_type`),
    CONSTRAINT `modelo_tem_permissoes_permission_id_foreign`
        FOREIGN KEY (`permission_id`) REFERENCES `permissoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `modelo_tem_papeis` (
    `role_id` bigint unsigned NOT NULL,
    `model_type` varchar(255) NOT NULL,
    `model_id` bigint unsigned NOT NULL,
    PRIMARY KEY (`role_id`, `model_id`, `model_type`),
    KEY `modelo_tem_papeis_model_id_model_type_index` (`model_id`, `model_type`),
    CONSTRAINT `modelo_tem_papeis_role_id_foreign`
        FOREIGN KEY (`role_id`) REFERENCES `papeis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `papel_tem_permissoes` (
    `permission_id` bigint unsigned NOT NULL,
    `role_id` bigint unsigned NOT NULL,
    PRIMARY KEY (`permission_id`, `role_id`),
    CONSTRAINT `papel_tem_permissoes_permission_id_foreign`
        FOREIGN KEY (`permission_id`) REFERENCES `permissoes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `papel_tem_permissoes_role_id_foreign`
        FOREIGN KEY (`role_id`) REFERENCES `papeis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Empresa
-- -----------------------------------------------------------------------------

CREATE TABLE `empresas` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `nome_empresa` varchar(255) DEFAULT NULL,
    `razao_social` varchar(255) DEFAULT NULL,
    `cnpj` varchar(20) DEFAULT NULL,
    `endereco` varchar(255) DEFAULT NULL,
    `cidade` varchar(255) DEFAULT NULL,
    `estado` varchar(2) DEFAULT NULL,
    `cep` varchar(10) DEFAULT NULL,
    `telefone` varchar(20) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `site` varchar(255) DEFAULT NULL,
    `caminho_logo` varchar(255) DEFAULT NULL,
    `criado_em` timestamp NULL DEFAULT NULL,
    `atualizado_em` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Clientes
-- -----------------------------------------------------------------------------

CREATE TABLE `clientes` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `nome` varchar(255) NOT NULL,
    `documento` varchar(255) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `telefone` varchar(255) DEFAULT NULL,
    `cidade` varchar(255) DEFAULT NULL,
    `estado` varchar(2) DEFAULT NULL,
    `endereco` text,
    `rua` varchar(255) DEFAULT NULL,
    `numero` varchar(20) DEFAULT NULL,
    `bairro` varchar(255) DEFAULT NULL,
    `cep` varchar(10) DEFAULT NULL,
    `ativo` tinyint(1) NOT NULL DEFAULT 1,
    `criado_em` timestamp NULL DEFAULT NULL,
    `atualizado_em` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cliente_usuario` (
    `cliente_id` bigint unsigned NOT NULL,
    `usuario_id` bigint unsigned NOT NULL,
    `criado_em` timestamp NULL DEFAULT NULL,
    `atualizado_em` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`cliente_id`, `usuario_id`),
    CONSTRAINT `cliente_usuario_cliente_id_foreign`
        FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `cliente_usuario_usuario_id_foreign`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Ordens de serviço
-- -----------------------------------------------------------------------------

CREATE TABLE `ordens_servico` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `cliente_id` bigint unsigned NOT NULL,
    `tecnico_id` bigint unsigned DEFAULT NULL,
    `tipo` varchar(255) NOT NULL COMMENT 'treinamento, visita_tecnica, manutencao',
    `titulo` varchar(255) NOT NULL,
    `descricao` text,
    `data_agendada` date DEFAULT NULL,
    `status` varchar(255) NOT NULL DEFAULT 'pendente' COMMENT 'pendente, em_andamento, concluida, cancelada',
    `participante` varchar(255) DEFAULT NULL,
    `participante_telefone` varchar(20) DEFAULT NULL,
    `tempo_segundos` int unsigned NOT NULL DEFAULT 0,
    `pausada` tinyint(1) NOT NULL DEFAULT 0,
    `descricao_servicos` text,
    `participante_1` varchar(255) DEFAULT NULL,
    `participante_2` varchar(255) DEFAULT NULL,
    `participante_3` varchar(255) DEFAULT NULL,
    `participante_4` varchar(255) DEFAULT NULL,
    `observacoes` text,
    `iniciada_em` timestamp NULL DEFAULT NULL,
    `finalizada_em` timestamp NULL DEFAULT NULL,
    `criado_em` timestamp NULL DEFAULT NULL,
    `atualizado_em` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `ordens_servico_cliente_id_foreign` (`cliente_id`),
    KEY `ordens_servico_tecnico_id_foreign` (`tecnico_id`),
    CONSTRAINT `ordens_servico_cliente_id_foreign`
        FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `ordens_servico_tecnico_id_foreign`
        FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comentarios_ordem_servico` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `ordem_servico_id` bigint unsigned NOT NULL,
    `usuario_id` bigint unsigned DEFAULT NULL,
    `autor` varchar(255) NOT NULL,
    `texto` text NOT NULL,
    `criado_em` timestamp NULL DEFAULT NULL,
    `atualizado_em` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `comentarios_ordem_servico_ordem_servico_id_foreign` (`ordem_servico_id`),
    KEY `comentarios_ordem_servico_usuario_id_foreign` (`usuario_id`),
    CONSTRAINT `comentarios_ordem_servico_ordem_servico_id_foreign`
        FOREIGN KEY (`ordem_servico_id`) REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE,
    CONSTRAINT `comentarios_ordem_servico_usuario_id_foreign`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `pausas_ordem_servico` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `ordem_servico_id` bigint unsigned NOT NULL,
    `motivo` text NOT NULL,
    `pausada_em` timestamp NOT NULL,
    `criado_em` timestamp NULL DEFAULT NULL,
    `atualizado_em` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `pausas_ordem_servico_ordem_servico_id_foreign` (`ordem_servico_id`),
    CONSTRAINT `pausas_ordem_servico_ordem_servico_id_foreign`
        FOREIGN KEY (`ordem_servico_id`) REFERENCES `ordens_servico` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tarefas
-- -----------------------------------------------------------------------------

CREATE TABLE `tarefas` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `titulo` varchar(255) NOT NULL,
    `descricao` text,
    `status` varchar(255) NOT NULL DEFAULT 'pendente',
    `prioridade` varchar(255) NOT NULL DEFAULT 'media',
    `data_vencimento` date DEFAULT NULL,
    `responsavel_id` bigint unsigned DEFAULT NULL,
    `categoria` varchar(255) NOT NULL DEFAULT 'operacional',
    `data_inicio` date DEFAULT NULL,
    `tempo_segundos` int unsigned NOT NULL DEFAULT 0,
    `recorrencia` varchar(255) NOT NULL DEFAULT 'nenhuma',
    `criado_em` timestamp NULL DEFAULT NULL,
    `atualizado_em` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `tarefas_responsavel_id_foreign` (`responsavel_id`),
    CONSTRAINT `tarefas_responsavel_id_foreign`
        FOREIGN KEY (`responsavel_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comentarios_tarefa` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `tarefa_id` bigint unsigned NOT NULL,
    `usuario_id` bigint unsigned DEFAULT NULL,
    `autor` varchar(255) NOT NULL,
    `texto` text NOT NULL,
    `criado_em` timestamp NULL DEFAULT NULL,
    `atualizado_em` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `comentarios_tarefa_tarefa_id_foreign` (`tarefa_id`),
    KEY `comentarios_tarefa_usuario_id_foreign` (`usuario_id`),
    CONSTRAINT `comentarios_tarefa_tarefa_id_foreign`
        FOREIGN KEY (`tarefa_id`) REFERENCES `tarefas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `comentarios_tarefa_usuario_id_foreign`
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `anexos_tarefa` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `tarefa_id` bigint unsigned NOT NULL,
    `nome_arquivo` varchar(255) NOT NULL,
    `caminho` varchar(255) NOT NULL,
    `tamanho_bytes` bigint unsigned NOT NULL DEFAULT 0,
    `tipo_mime` varchar(255) DEFAULT NULL,
    `criado_em` timestamp NULL DEFAULT NULL,
    `atualizado_em` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `anexos_tarefa_tarefa_id_foreign` (`tarefa_id`),
    CONSTRAINT `anexos_tarefa_tarefa_id_foreign`
        FOREIGN KEY (`tarefa_id`) REFERENCES `tarefas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Após importar: php artisan db:seed
