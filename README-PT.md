# WP Auto Content Pro

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-2.0.0-orange.svg)](https://github.com/ybngf/WP-Auto-Content-Pro)

**Plugin profissional de geração automática de conteúdo com IA para WordPress com distribuição multiplataforma em redes sociais.**

Desenvolvido por [Autometa](https://autometa.com.br)

🌐 [English Version](README-EN.md)

---

## 📋 Índice

- [Visão Geral](#-visão-geral)
- [Funcionalidades](#-funcionalidades)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Provedores de IA](#-provedores-de-ia)
- [Redes Sociais](#-redes-sociais)
- [Geração de Imagens](#-geração-de-imagens)
- [Agendamento](#-agendamento)
- [Gerenciamento de Tópicos](#-gerenciamento-de-tópicos)
- [SEO](#-seo)
- [Webhooks](#-webhooks)
- [Logs e Monitoramento](#-logs-e-monitoramento)
- [Idiomas Suportados](#-idiomas-suportados)
- [FAQ](#-faq)
- [Changelog](#-changelog)
- [Licença](#-licença)

---

## 🎯 Visão Geral

O **WP Auto Content Pro** é um plugin WordPress completo que automatiza a criação de conteúdo usando inteligência artificial e distribui automaticamente nas principais redes sociais. Ideal para blogs, portais de notícias, sites de nicho e qualquer projeto que necessite de conteúdo constante e de alta qualidade.

### Por que usar o WP Auto Content Pro?

- ⚡ **Automação total** — Configure uma vez, gere conteúdo 24/7
- 🧠 **4 provedores de IA** com fallback automático entre eles
- 📱 **6 redes sociais** integradas nativamente
- 🖼️ **Imagens automáticas** via DALL-E 3, Unsplash ou Picsum
- 🔍 **SEO otimizado** com suporte a Yoast SEO e RankMath
- 🌍 **30 idiomas** suportados para geração de conteúdo
- 📊 **Dashboard completo** com estatísticas e logs detalhados

---

## ✨ Funcionalidades

### Geração de Conteúdo com IA
- Suporte a **OpenAI GPT-4o**, **Google Gemini**, **Anthropic Claude** e **DeepSeek**
- Sistema de **fallback automático** entre provedores (se um falhar, tenta o próximo)
- **Retry automático** (até 2 tentativas) em caso de falha na geração
- Geração de artigos completos com título, conteúdo, excerto, tags, categoria e meta description
- Legendas para redes sociais geradas automaticamente
- Prompts para geração de imagens incluídos na resposta da IA
- Controle de **tamanho do artigo**: curto (600-900), médio (900-1400) ou longo (1400-2200 palavras)

### Distribuição em Redes Sociais
- **Twitter/X** — OAuth 1.0a com upload de mídia
- **Threads** — API Meta com posts de texto e imagem
- **Instagram** — API Graph Meta v19.0 para contas Business
- **Facebook** — Publicação em Páginas com links e fotos
- **TikTok** — Content Posting API v2 com fotos
- **LinkedIn** — UGC Posts API v2 com artigos

### Imagens Automáticas
- **DALL-E 3** (OpenAI) — Imagens geradas por IA sob medida
- **Unsplash** — Fotos profissionais de alta qualidade
- **Picsum** — Fallback gratuito sem necessidade de API key
- Cadeia de fallback: DALL-E → Unsplash → Picsum
- Detecção automática de tipo de arquivo (JPG, PNG, WebP, GIF)
- Texto alternativo (alt text) definido automaticamente para SEO

### Agendamento Inteligente
- Cron personalizado com intervalos de 30min, 2h, 6h ou diário
- **Janela de postagem** configurável (ex: 08:00 às 20:00)
- **Limite diário** de posts (1 a 24 por dia)
- Suporte a timezone personalizado
- Lock por transient para evitar execuções concorrentes

### Dashboard Administrativo
- Estatísticas em tempo real (posts gerados, compartilhamentos sociais)
- Status de todos os provedores de IA e redes sociais
- Botão **"Gerar Agora"** para criação instantânea
- Atividade recente com detalhes de cada publicação
- Ações rápidas para navegação fácil

### Gerenciamento de Tópicos
- CRUD completo de tópicos (criar, editar, excluir)
- **Importação em massa via CSV**
- Ações em lote (ativar, pausar, excluir)
- Tipos de conteúdo: Artigo ou Tutorial
- Frequência por tópico: horária, a cada 2h, 6h, diária ou semanal
- Categoria opcional (ou deixar a IA decidir)

### SEO Integrado
- Compatibilidade nativa com **Yoast SEO**
- Compatibilidade nativa com **RankMath**
- Meta description gerada automaticamente pela IA
- Título SEO definido automaticamente
- Tags geradas pela IA para cada post

---

## 📋 Requisitos

| Requisito       | Mínimo          |
|-----------------|-----------------|
| WordPress       | 5.8+            |
| PHP             | 7.4+            |
| MySQL           | 5.6+ / MariaDB  |
| Extensão PHP    | `json`, `curl`  |

---

## 🚀 Instalação

### Instalação Manual

1. Faça o download do plugin:
   ```bash
   git clone https://github.com/ybngf/WP-Auto-Content-Pro.git
   ```

2. Copie a pasta para o diretório de plugins do WordPress:
   ```
   wp-content/plugins/wp-auto-content-pro/
   ```

3. Acesse **Plugins** no painel administrativo do WordPress

4. Ative o plugin **WP Auto Content Pro**

5. Acesse **WP Auto Content → Dashboard** no menu lateral

### Instalação via ZIP

1. Baixe o arquivo ZIP do [repositório GitHub](https://github.com/ybngf/WP-Auto-Content-Pro)
2. No WordPress, vá em **Plugins → Adicionar Novo → Enviar Plugin**
3. Selecione o arquivo ZIP e clique em **Instalar Agora**
4. Ative o plugin

---

## ⚙️ Configuração

### Configuração Inicial

Após ativar o plugin, siga estes passos:

1. **Configurar Provedor de IA** — Acesse **WP Auto Content → Configurações → IA** e insira a API key do seu provedor preferido
2. **Adicionar Tópicos** — Acesse **WP Auto Content → Tópicos** e adicione os assuntos que deseja gerar conteúdo
3. **Ativar Agendamento** — Em **Configurações → Agenda**, ative a postagem automática
4. **(Opcional) Redes Sociais** — Configure as credenciais das redes sociais desejadas

### Dica para Cron Confiável

O WP-Cron depende de visitas ao site para executar. Para agendamento mais confiável, configure um cron real no servidor:

```bash
*/30 * * * * wget -q -O - https://seusite.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

---

## 🧠 Provedores de IA

### OpenAI (GPT-4o)
- **Modelos disponíveis**: gpt-4o, gpt-4o-mini, gpt-4-turbo, gpt-4
- **Obter API Key**: [platform.openai.com](https://platform.openai.com/api-keys)
- Também utilizado para geração de imagens via DALL-E 3

### Google Gemini
- **Modelos disponíveis**: gemini-1.5-pro, gemini-1.5-flash, gemini-pro
- **Obter API Key**: [aistudio.google.com](https://aistudio.google.com/app/apikey)

### Anthropic Claude
- **Modelos disponíveis**: claude-opus-4-6, claude-sonnet-4-6, claude-3-opus-20240229, claude-3-sonnet-20240229
- **Obter API Key**: [console.anthropic.com](https://console.anthropic.com/settings/keys)

### DeepSeek
- **Modelos disponíveis**: deepseek-chat, deepseek-coder
- **Obter API Key**: [platform.deepseek.com](https://platform.deepseek.com/api_keys)
- Opção mais econômica com boa qualidade

### Sistema de Fallback

Se o provedor principal falhar, o plugin tenta automaticamente os outros provedores configurados na seguinte ordem:

1. Provedor selecionado (primário)
2. OpenAI
3. Google Gemini
4. Anthropic Claude
5. DeepSeek

O provedor primário é ignorado na sequência de fallback para evitar dupla tentativa.

---

## 📱 Redes Sociais

### Twitter / X

**Tipo de autenticação**: OAuth 1.0a (User Context)

Credenciais necessárias:
- API Key (Consumer Key)
- API Secret (Consumer Secret)
- Access Token
- Access Token Secret

**Template de post personalizável** com placeholders: `{title}`, `{url}`, `{hashtags}`

[Obter credenciais →](https://developer.twitter.com/en/portal/projects-and-apps)

### Threads (Meta)

Credenciais necessárias:
- Access Token
- User ID

Posts com texto e imagem. Usa o sistema de 2 etapas da API (criar container + publicar).

### Instagram (Business)

Credenciais necessárias:
- Page Access Token
- Instagram Business Account ID

Requer imagem obrigatória. Usa o sistema de 3 etapas (criar container → aguardar → publicar).

[Configurar conta Business →](https://business.facebook.com/)

### Facebook Pages

Credenciais necessárias:
- Page Access Token
- Page ID

Suporta posts com link (preview automático) e posts com foto.

### TikTok

Credenciais necessárias:
- Access Token

Usa a Content Posting API v2. Suporta posts com foto.

[Developer Portal →](https://developers.tiktok.com/)

### LinkedIn

Credenciais necessárias:
- Access Token
- Author URN (auto-detectado)

Publica artigos profissionais com link preview via UGC Posts API v2.

---

## 🖼️ Geração de Imagens

### DALL-E 3 (OpenAI)

| Configuração  | Opções                                           |
|---------------|--------------------------------------------------|
| Modelo        | DALL-E 3, DALL-E 2                               |
| Tamanho       | 1792x1024 (paisagem), 1024x1024 (quadrado), 1024x1792 (retrato) |
| Qualidade     | Standard                                          |

O prompt de imagem é gerado automaticamente pela IA junto com o artigo.

### Unsplash

- Necessita de Access Key gratuita
- Busca imagens por relevância ao título do artigo
- Orientação landscape automática

[Obter API Key →](https://unsplash.com/developers)

### Picsum (Fallback Gratuito)

- Não necessita de API key
- Imagens aleatórias de alta qualidade
- Resolução 1792x1024
- Usado automaticamente quando DALL-E e Unsplash falham

---

## ⏰ Agendamento

### Configurações de Agenda

| Opção              | Valores                          |
|--------------------|----------------------------------|
| Posts por dia       | 1 a 24                           |
| Janela de postagem  | Hora início e fim (00:00-23:00)  |
| Status padrão       | Publicar, Rascunho ou Pendente   |
| Timezone            | Qualquer timezone PHP válido     |

### Intervalos de Cron

O intervalo do cron é calculado automaticamente com base nos posts por dia:

| Posts/dia   | Intervalo        |
|-------------|------------------|
| 24+         | A cada 1 hora    |
| 12-23       | A cada 2 horas   |
| 4-11        | A cada 6 horas   |
| 1-3         | Diário           |

---

## 📝 Gerenciamento de Tópicos

### Formato CSV para Importação

```csv
"WordPress Security Tips", "article", "daily", "Security"
"Como usar o WooCommerce", "tutorial", "weekly", "E-commerce"
"SEO Best Practices 2024", "article", "every_6h", "Marketing"
```

**Colunas**: tópico, tipo (`article`/`tutorial`), frequência (`hourly`/`every_2h`/`every_6h`/`daily`/`weekly`), categorias

### Frequência por Tópico

Cada tópico tem sua própria frequência de geração. O sistema seleciona automaticamente o tópico com maior prioridade (baseado na última geração e na frequência configurada).

---

## 🔍 SEO

### Metadados Gerados Automaticamente

- **Meta Description** — Gerada pela IA, salva no post e nos campos do Yoast SEO e RankMath
- **Título SEO** — Definido automaticamente nos campos do Yoast e RankMath
- **Tags** — Geradas pela IA e atribuídas ao post
- **Categorias** — Podem ser definidas por tópico ou geradas pela IA
- **Texto alternativo de imagem** — Definido automaticamente para a imagem destacada
- **Excerto** — Gerado pela IA para SEO e redes sociais

---

## 🔔 Webhooks

Configure uma URL de webhook para receber notificações quando um novo post é publicado:

### Payload do Webhook

```json
{
  "event": "post_published",
  "post_id": 123,
  "post_url": "https://seusite.com/post-exemplo/",
  "post_title": "Título do Post",
  "provider": "openai",
  "timestamp": "2024-01-15T10:30:00-03:00",
  "site_url": "https://seusite.com"
}
```

### Headers

| Header              | Descrição                      |
|---------------------|--------------------------------|
| `Content-Type`      | `application/json`             |
| `X-WPAC-Event`      | `post_published`               |
| `User-Agent`        | `WPAutoContentPro/2.0.0`       |
| `X-WPAC-Signature`  | HMAC SHA-256 (se configurado)  |

### Segurança do Webhook

Configure um **Webhook Secret** nas configurações avançadas para receber uma assinatura HMAC SHA-256 no header `X-WPAC-Signature`, permitindo validar a autenticidade da notificação.

---

## 📊 Logs e Monitoramento

### Log de Posts

Cada geração de conteúdo é registrada com:
- Título do post e link
- Tópico utilizado
- Provedor de IA usado
- Status do post (publicado, rascunho, erro)
- Mensagem de erro (quando aplicável)
- Data e hora da geração

### Log de Redes Sociais

Para cada compartilhamento social:
- Plataforma
- Status (sucesso, falha)
- Mensagem de erro
- ID do post na plataforma
- URL do post na plataforma

### Modo Debug

Ative o **Modo Debug** nas configurações avançadas para registrar logs detalhados no `error_log` do PHP. Útil para troubleshooting mas deve ser desativado em produção.

---

## 🌍 Idiomas Suportados

O plugin suporta geração de conteúdo em **30 idiomas**:

| Idioma           | Código  | Idioma           | Código  |
|------------------|---------|------------------|---------|
| Inglês           | `en`    | Turco            | `tr`    |
| Espanhol         | `es`    | Sueco            | `sv`    |
| Francês          | `fr`    | Dinamarquês      | `da`    |
| Alemão           | `de`    | Norueguês        | `no`    |
| Italiano         | `it`    | Finlandês        | `fi`    |
| Português (BR)   | `pt-br` | Tcheco           | `cs`    |
| Português (PT)   | `pt`    | Tailandês        | `th`    |
| Holandês         | `nl`    | Vietnamita       | `vi`    |
| Polonês          | `pl`    | Indonésio        | `id`    |
| Russo            | `ru`    | Malaio           | `ms`    |
| Japonês          | `ja`    | Ucraniano        | `uk`    |
| Chinês           | `zh`    | Romeno           | `ro`    |
| Coreano          | `ko`    | Húngaro          | `hu`    |
| Árabe            | `ar`    | Grego            | `el`    |
| Hindi            | `hi`    | Hebraico         | `he`    |

---

## ❓ FAQ

### O plugin funciona sem API key?
Não. É necessário pelo menos uma API key de um provedor de IA (OpenAI, Gemini, Claude ou DeepSeek) para gerar conteúdo.

### Qual provedor de IA é recomendado?
O **OpenAI GPT-4o** oferece a melhor qualidade geral. O **DeepSeek** é a opção mais econômica. Configure múltiplos provedores para ter fallback automático.

### As redes sociais são obrigatórias?
Não. O compartilhamento social é totalmente opcional. O plugin funciona perfeitamente apenas gerando posts no WordPress.

### O plugin cria as categorias automaticamente?
Sim. Se a IA sugerir uma categoria que não existe, o plugin a cria automaticamente no WordPress.

### Posso gerar conteúdo manualmente?
Sim. Use o botão **"Gerar Agora"** no Dashboard para gerar um post instantaneamente, independentemente do agendamento.

### O que acontece quando desinstalo o plugin?
Todos os dados do plugin são removidos: tabelas do banco de dados, opções, transients, metadados de posts e eventos cron. Os posts do WordPress gerados pelo plugin são mantidos.

### É compatível com multisites?
O plugin funciona em cada site individualmente em uma instalação multisite.

### Posso personalizar o prompt da IA?
O prompt é otimizado internamente para gerar conteúdo de alta qualidade. Você controla o resultado através do tópico, tipo de conteúdo, idioma e tamanho do artigo.

---

## 📝 Changelog

### 2.0.0 (2024)
- ✨ Versão completamente reformulada e profissionalizada
- 🧠 Suporte a 4 provedores de IA com fallback automático
- 📱 6 redes sociais integradas
- 🖼️ 3 fontes de imagem com cadeia de fallback
- 🌍 30 idiomas suportados
- 📊 Dashboard com estatísticas em tempo real
- 🔔 Webhooks com assinatura HMAC
- 🔄 Sistema de retry para geração de conteúdo
- 🔐 Validação de arquivos CSV melhorada
- 🗑️ Limpeza completa na desinstalação
- 🔍 Compatibilidade aprimorada com Yoast SEO e RankMath

### 1.0.0
- 🎉 Lançamento inicial

---

## 📄 Licença

Este plugin é distribuído sob a licença [GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0.html).

Desenvolvido com ❤️ por [Autometa](https://autometa.com.br)
