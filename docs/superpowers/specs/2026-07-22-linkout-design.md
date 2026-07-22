# LinkOut — Design Spec

**Date:** 2026-07-22
**Status:** Approved by Khang (brainstorming session)
**URL:** https://linkout.didudi.com

## What it is

A pseudonymous community for people who have left a company. Members post
stories about their experience, rate the company across six dimensions, and
vote and comment on each other's stories. Companies are ranked by aggregated
ratings. Reporting abusive content requires a verified corporate email, and
reports from the discussed company's own domain carry extra weight.

## Core decisions (from brainstorming)

| Decision | Choice |
|---|---|
| Verification gate | Reporting only. Posting/voting need just a confirmed account. |
| Identity model | Pseudonymous accounts: any email + password, random public handle. |
| Company identity | Domain-keyed registry; first poster creates company (name + domain). |
| Reporter email | Any corporate email may report; `@<company-domain>` reports ranked higher. |
| Moderation | Auto-hide after 3 pending reports from 3 **distinct** verified corporate domains; admin restores or removes. |
| Ratings | Leadership, Work culture, Comp & benefits, Work-life balance, Career growth, Exit experience (1–5 each) + Recommend yes/no. |
| V1 scope | Comments (flat), company profile pages, search, EN+VI UI. |
| Stack | PHP 8.4 + MariaDB, server-rendered, on existing didudi Hetzner box (Option A). |

## Architecture

- nginx vhost `linkout.didudi.com` → php-fpm 8.4, docroot `/var/www/linkout/public`.
- Plain PHP, front controller `public/index.php` with a small router. No
  framework. PHP partial templates. PDO → MariaDB database `linkout`.
- PHP sessions, secure/httponly/samesite cookies.
- Email (confirmation links, reset links, report codes) via the server's local
  mail setup; if none exists, msmtp relay (provider TBD at deploy time — this
  is the one open infra question).
- Deploy: rsync from Mac, numbered `.sql` migration files, no CI.
- Local repo: `~/Development/Projects/linkout` (this repo).

### Routes

| Route | Purpose |
|---|---|
| `/` | Feed: tabs New (default) / Trending / Top-30d + sidebar rails |
| `/story/{id}` | Story page + flat comments |
| `/company/{domain}` | Company profile: per-dimension bars, recommend %, its stories |
| `/post` | New story form (verified users) |
| `/signup`, `/login`, `/logout`, `/confirm/{token}`, `/reset` | Auth |
| `/search?q=` | Companies first, then FULLTEXT story matches |
| `/api/vote`, `/api/report`, `/api/comment` | JSON endpoints (fetch, no reload) |
| `/admin` | Report queue, user bans, company edit/merge |

## Data model (MariaDB, 7 tables + settings)

- **users** — id, email (unique, never displayed), password_hash (bcrypt),
  handle (unique, auto-generated e.g. `QuietFalcon84`), email_verified_at,
  role (`user`/`admin`), banned_at, created_at.
- **companies** — id, domain (unique, normalized: lowercase, strip `www.`),
  name, created_by, created_at.
- **stories** — id, user_id, company_id, title, body (plain text, ≤10k chars),
  r_leadership, r_culture, r_benefits, r_balance, r_growth, r_exit
  (tinyint 1–5), recommend (bool), status (`active`/`auto_hidden`/`removed`),
  vote_score (cached), created_at. Editable/deletable by author for 24h only.
- **votes** — (user_id, story_id) unique, value (+1/−1). Toggle/flip; cached
  score updated in same transaction.
- **comments** — id, story_id, user_id, body, status, created_at. Flat, no
  votes in v1. Verified users only.
- **reports** — id, story_id, reporter_user_id, corp_email, corp_domain,
  is_company_match (corp_domain == story's company domain), reason
  (false_info/doxxing/harassment/spam/other + text), verify_code, verified_at,
  status (`pending`/`dismissed`/`actioned`), created_at. One per user per story.
- **settings** — key/value (report threshold = 3, etc.).

## Ranking (computed in SQL, no cron)

- **Trending:** `vote_score / POW(hours_since_post + 2, 1.5)`, last 14 days.
- **Most liked:** raw vote_score, last 30 days.
- **Highly rated companies:** Bayesian-smoothed mean of the six ratings —
  `(company_sum + global_avg * 10) / (company_count + 10)` — minimum 3
  stories to appear in the rail.

## Key flows

### Posting
Verified user → `/post` → company typeahead against registry; no match →
inline create (name + domain, validated as plausible domain shape) → title,
body, six star rows, recommend toggle. Rate limit: 3 stories/day.

### Reporting
Report button → modal: reason → corporate email. Free-mail domains rejected
via blocklist (gmail, yahoo, outlook, proton, zoho, common VN free mail).
6-digit code sent, 15-min expiry, 5 attempts max, 5 verification emails/hour
per user. Verified code files the report; domain stored; company match flagged.

### Auto-hide
3 `pending` reports from 3 distinct verified corp domains → story status
`auto_hidden` (hidden from feeds; story page shows "under review"). Admin:
**restore** (dismisses those reports; immune to re-hide unless *new* reports
arrive), or **remove** permanently.

### Admin queue
Sorted company-match first, then oldest. Story shown inline with report
reasons. Actions: restore / remove / dismiss. Also: user ban toggle, company
name edit/merge.

## Visual design — Jeton system

Full Jeton token sheet as `:root` CSS custom properties (see
`docs/superpowers/specs/jeton-style-reference.md` — the style block supplied
by Khang, to be committed alongside this spec). Font: **Inter** (self-hosted
variable font) substituting Sequel Sans; weights 400/450/500 only, never bold.

- Canvas `#ffffff`; body text Ink Roast `#360802`; Signal Orange `#f73b20` is
  the only chromatic voice: logo, active nav, active vote arrows, primary
  buttons, rating stars.
- Feed hero: oversized editorial headline (72–106px, weight 500, LH 1.0),
  EN "Left. Not silenced." / VI equivalent.
- Story cards: white, 16px radius, inverted shadow
  `rgba(0,0,0,0.05) 0 -4px 16px`, company chip pill on Linen Blush `#fdedea`,
  vote column left.
- Sidebar rails use the category accents once each: Coral Red = Trending,
  Cobalt Blue = Most liked, Emerald Green = Highly rated.
- Rating inputs: orange stars on brand tint `rgba(247,59,32,0.05)`, 16px radius.
- Report modal: frosted glass + `--shadow-lg`.
- Buttons 12px radius, 14px, weight 450, 0.03em tracking; pills 9999px.
- Mobile: sidebar rails collapse below the feed.

## i18n

`lang/en.php` + `lang/vi.php` arrays, `t('key')` helper, nav toggle persisted
in cookie, default from `Accept-Language`. Only UI chrome is translated; user
content stays as written.

## Error handling & limits

- All writes in PDO transactions; JSON endpoints return `{ok, error}`;
  inline UI error messages.
- Rate limits enforced in DB: 3 stories/day, 20 comments/hour, 5 verification
  emails/hour, 5 code attempts per report.
- Mail failures surfaced to the user, never silent.
- `display_errors` off; single error_log.
- Password reset: standard emailed link flow (included in v1).

## Testing

- PHPUnit: ranking math, Bayesian aggregation, domain normalization,
  free-mail blocklist, distinct-domain auto-hide counting, rate limits.
- Route smoke script against a seeded test DB asserting 200/302 on every route.
- Manual browser pass of real flows before each deploy.

## Deployment checklist (one-time)

1. DNS A record `linkout.didudi.com` → Hetzner IP (Khang, at DNS provider).
2. nginx vhost + `certbot` cert.
3. MariaDB: create database `linkout` + dedicated user.
4. Run migrations, create admin account.
5. Verify outbound mail works from the box (or configure msmtp).

## Out of scope for v1

Comment votes, threaded comments, notifications, profile pages beyond the
handle, company logos, OAuth login, API for third parties, mobile app.
