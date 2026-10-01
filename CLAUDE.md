# Tunduk Trips (go-kyrgyzstan)

- Read `docs/PLAN.md` before starting a stage; stages map to step numbers from the launch document (e.g. 3.1–3.5).
- One stage = one branch + one PR into `main`.
- Site copy is English; docs and commit discussion may be Russian.
- Money is stored in integer cents, currency USD. Tour dates are `date` values in Asia/Bishkek.
- Operator contacts (phone, WhatsApp, email) must never appear in public API responses before `deposit_paid`.
- `web/` is Next.js 16: read `web/AGENTS.md` and the docs in `web/node_modules/next/dist/docs/` before writing Next.js code.
