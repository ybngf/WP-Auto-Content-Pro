# WP Auto Content Pro

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-2.0.0-orange.svg)](https://github.com/ybngf/WP-Auto-Content-Pro)
[![Author](https://img.shields.io/badge/Author-Autometa-brightgreen.svg)](https://autometa.com.br)

> **Intelligent Content Automation at Scale** — Generate professional blog posts, articles, and social media content automatically with AI, then distribute across 6 platforms instantly.

<div align="center">

**[Autometa](https://autometa.com.br) • [Support](https://github.com/ybngf/WP-Auto-Content-Pro/discussions) • [Issues](https://github.com/ybngf/WP-Auto-Content-Pro/issues) • [Changelog](CHANGELOG.md)**

---

**[📖 Read in Portuguese](#-português) | [🇬🇧 English Documentation](#-english)**

</div>

---

## 🌟 Highlights

- **🤖 AI-Powered** — 4 AI providers (OpenAI, Gemini, Claude, DeepSeek) with automatic intelligent failover
- **📱 Omnichannel** — Auto-publish to Twitter, Threads, Instagram, Facebook, TikTok, and LinkedIn
- **🖼️ Image Generation** — DALL-E 3, Unsplash, or Picsum with smart fallback chain
- **🔍 SEO-Ready** — Native integration with Yoast SEO & RankMath
- **⏰ Smart Scheduling** — Cron-based automation with customizable windows
- **🌍 30 Languages** — Generate content in any language globally
- **📊 Real-Time Dashboard** — Monitor all statistics and logs
- **🔐 Enterprise-Grade** — Webhooks with HMAC signatures, complete data cleanup on uninstall

---

## 🎯 English

### Overview

**WP Auto Content Pro** is a comprehensive WordPress plugin that automates content creation using artificial intelligence and automatically distributes it across major social media platforms. Ideal for blogs, news portals, niche websites, and any project that requires constant, high-quality content.

### Quick Start

1. **Install** — Download or clone this repository into `wp-content/plugins/`
2. **Activate** — Enable the plugin from WordPress admin
3. **Configure** — Add your AI API key and social credentials
4. **Create Topics** — Define what content to generate
5. **Automate** — Schedule and let it run 24/7

[✅ Full Installation & Configuration Guide](README-EN.md)

---

### Use Cases

| Use Case | Benefit |
|----------|---------|
| **News Portals** | Publish 20+ articles daily auto-formatted for search engines |
| **Marketing Agencies** | Client content at scale with multi-language support |
| **SaaS Companies** | Consistent blog posts for SEO without hiring writers |
| **Digital Publishers** | Real-time content distribution across all platforms |
| **Affiliate Sites** | High-volume niche content generation with monetization |

---

### Key Features

#### Content Generation
- Multi-AI provider support with intelligent failover
- 30+ language support
- Customizable article lengths (short/medium/long)
- AI-generated titles, descriptions, tags, categories
- Featured image auto-generation via DALL-E 3, Unsplash, or Picsum
- SEO meta-data optimization
- Social media captions auto-generated

#### Social Distribution  
- **Twitter/X** — OAuth 1.0a, with images
- **Threads** — Meta API, text & image
- **Instagram** — Business accounts, full media
- **Facebook** — Pages, link previews
- **TikTok** — Content API v2, photo posts
- **LinkedIn** — Professional articles via UGC API

#### Automation & Intelligence
- Smart topic scheduling (hourly to weekly)
- Cron-based execution with window control
- Automatic retry on AI failure (up to 2 attempts)
- Provider fallback system
- Duplicate prevention via transients
- Webhook notifications on publish with HMAC signatures

---

### System Requirements

| Requirement | Minimum |
|------------|---------|
| WordPress  | 5.8+    |
| PHP        | 7.4+    |
| MySQL      | 5.6+    |
| API Key    | At least 1 (OpenAI, Gemini, Claude, or DeepSeek) |

---

### Installation

#### Via Git
```bash
cd wp-content/plugins
git clone https://github.com/ybngf/WP-Auto-Content-Pro.git
```

#### Via ZIP
1. Download: [Latest Release](https://github.com/ybngf/WP-Auto-Content-Pro/releases)
2. Upload via WordPress: Plugins → Add New → Upload
3. Activate

[Complete Setup Guide →](README-EN.md#-installation)

---

### Configuration Overview

**Supported AI Models:**
- OpenAI: gpt-4o, gpt-4o-mini, gpt-4-turbo  
- Gemini: gemini-1.5-pro, gemini-1.5-flash
- Claude: claude-opus, claude-sonnet
- DeepSeek: deepseek-chat, deepseek-coder

**Image Sources (fallback order):**
1. DALL-E 3 — AI-generated, custom prompts
2. Unsplash — Quality photos, requires free API key
3. Picsum — Random high-quality, no API needed

**30 Languages Supported:**
Portuguese, English, Spanish, French, German, Italian, Dutch, Polish, Russian, Japanese, Chinese, Korean, Arabic, Hindi, Turkish, Swedish, Danish, Norwegian, Finnish, Czech, Thai, Vietnamese, Indonesian, Malay, Ukrainian, Romanian, Hungarian, Greek, Hebrew

---

### Documentation

- **[English Docs](README-EN.md)** — Complete technical documentation, API guides, troubleshooting
- **[Portuguese Docs](README-PT.md)** — Documentação completa em Português
- **[Changelog](CHANGELOG.md)** — Version history and updates
- **[License](LICENSE)** — GPL-2.0+ open source license

---

## 🇧🇷 Português

### Visão Geral

**WP Auto Content Pro** é um plugin WordPress profissional que automatiza completamente a criação de conteúdo usando IA e o distribui automaticamente em 6 redes sociais. Ideal para blogs, agências, portais de notícias e qualquer negócio que necessite de conteúdo consistente em escala.

### Começar Rápido

1. **Instalar** — Baixe ou clone este repositório em `wp-content/plugins/`
2. **Ativar** — Ative o plugin no painel WordPress
3. **Configurar** — Adicione sua chave de API e credenciais sociais
4. **Criar Tópicos** — Defina o que gerar
5. **Automatizar** — Agende e deixe rodar 24/7

[✅ Guia de Instalação e Configuração Completo](README-PT.md)

---

### Casos de Uso

| Caso de Uso | Benefício |
|-------------|-----------|
| **Portais de Notícias** | Publique 20+ artigos/dia otimizados para buscadores |
| **Agências de Marketing** | Conteúdo em escala com suporte a múltiplos idiomas |
| **Empresas SaaS** | Posts de blog consistentes para SEO sem contratar escritores |
| **Editoras Digitais** | Distribuição automática em todos os canais sociais |
| **Sites de Afiliados** | Geração em larga escala com monetização |

---

### Recursos Principais

#### Geração de Conteúdo
- Suporte a múltiplos provedores IA com fallback inteligente
- 30+ idiomas suportados
- Tamanhos customizáveis de artigos (curto/médio/longo)
- Títulos, descrições, tags, categorias geradas por IA
- Geração automática de imagens (DALL-E 3, Unsplash, Picsum)
- Otimização de metadados SEO
- Legendas para redes sociais auto-geradas

#### Distribuição Social
- **Twitter/X** — OAuth 1.0a, com imagens
- **Threads** — API Meta, texto e imagem
- **Instagram** — Contas Business, mídia completa
- **Facebook** — Páginas, previsualizações automáticas
- **TikTok** — Content API v2, posts com foto
- **LinkedIn** — Artigos profissionais via UGC API

#### Automação Inteligente
- Agenda inteligente de tópicos (horária até semanal)
- Execução baseada em cron com janelas customizáveis
- Retry automático em falha de IA (até 2 tentativas)
- Sistema de fallback entre provedores
- Prevenção de duplicatas via transients
- Notificações de webhook com assinatura HMAC

---

### Requisitos do Sistema

| Requisito | Mínimo  |
|-----------|---------|
| WordPress | 5.8+    |
| PHP       | 7.4+    |
| MySQL     | 5.6+    |
| API Key   | Pelo menos 1 (OpenAI, Gemini, Claude ou DeepSeek) |

---

### Instalação

#### Via Git
```bash
cd wp-content/plugins
git clone https://github.com/ybngf/WP-Auto-Content-Pro.git
```

#### Via ZIP
1. Baixe: [Última Versão](https://github.com/ybngf/WP-Auto-Content-Pro/releases)
2. Upload no WordPress: Plugins → Adicionar Novo → Enviar
3. Ative

[Guia de Setup Completo →](README-PT.md#-instalação)

---

### Visão da Configuração

**Modelos de IA Suportados:**
- OpenAI: gpt-4o, gpt-4o-mini, gpt-4-turbo
- Gemini: gemini-1.5-pro, gemini-1.5-flash
- Claude: claude-opus, claude-sonnet  
- DeepSeek: deepseek-chat, deepseek-coder

**Fontes de Imagem (ordem de fallback):**
1. DALL-E 3 — Gerada por IA, prompts customizados
2. Unsplash — Fotos de qualidade, requer API key gratuita
3. Picsum — Aleatória de qualidade, sem API necessária

**30 Idiomas Suportados:**
Português, Inglês, Espanhol, Francês, Alemão, Italiano, Holandês, Polonês, Russo, Japonês, Chinês, Coreano, Árabe, Hindi, Turco, Sueco, Dinamarquês, Norueguês, Finlandês, Tcheco, Tailandês, Vietnamita, Indonésio, Malaio, Ucraniano, Romeno, Húngaro, Grego, Hebraico

---

### Documentação

- **[Docs em Inglês](README-EN.md)** — Documentação técnica completa, guias de API, troubleshooting
- **[Docs em Português](README-PT.md)** — Documentação completa em Português
- **[Changelog](CHANGELOG.md)** — Histórico de versões e atualizações
- **[Licença](LICENSE)** — Licença GPL-2.0+ de código aberto

---

## 🤝 Support

- **Issues**: [GitHub Issues](https://github.com/ybngf/WP-Auto-Content-Pro/issues)
- **Discussions**: [GitHub Discussions](https://github.com/ybngf/WP-Auto-Content-Pro/discussions)
- **Website**: [Autometa](https://autometa.com.br)

---

## 📄 License

This plugin is distributed under the **[GPL-2.0+ License](LICENSE)**.

Developed with ❤️ by **[Autometa](https://autometa.com.br)**
