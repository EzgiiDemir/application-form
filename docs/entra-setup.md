# Microsoft Entra ID setup

Production credentials can be managed from **Admin → Entegrasyonlar → Microsoft Entra**
(super_admin). Values saved there override `.env` at runtime; secrets are encrypted in
the database. `.env` remains a fallback for initial bootstrap and local development.

The public Apply experience has no login. The single `/login` page is only for
institutional users. It does not expose job titles, panels, candidates or
application data before authentication.

## App registration

1. In the ARUCAD Microsoft Entra tenant, create a **single-tenant** web app
   registration named `ARUCAD Apply`.
2. Add the production redirect URI exactly as
   `https://api.apply.arucad.edu.tr/auth/microsoft/callback` (use the actual
   Laravel domain if it differs). Add the local callback only to a separate
   development registration.
3. Create a client secret, store it in the deployment secret store and set
   `AZURE_CLIENT_ID`, `AZURE_CLIENT_SECRET`, `AZURE_TENANT_ID` and
   `AZURE_REDIRECT_URI`. Never place a secret in the repository or Next.js.
4. Configure the app to issue the email claim. The backend uses Entra's stable
   object id (`oid`) as `users.entra_id` and reconciles an existing account by
   email only during the first migration login.
5. Set `ENTRA_ALLOWED_DOMAINS` to the approved institutional domain list. The
   default is `arucad.edu.tr`; comma-separate verified additional domains only
   when ARUCAD confirms that they are institutional accounts.

## Authorization model

- Entra proves identity; Laravel stores the assigned application role.
- A first-time, allowed-domain user is created without a privileged role and
  sees `/access-pending`.
- A Super Admin assigns one of the server-side roles: `super_admin`,
  `faculty_admin`, `department_secretary`, `trainer`, or `student`.
- After every SSO callback Laravel chooses the destination internally: admin
  roles go to `/admin`, trainers to `/trainer`, students to `/student`.
- The public sign-in screen makes no role selection. Direct visits to a panel
  still pass Filament's panel access check, so changing a URL cannot escalate
  access.

## Before production

- Confirm ARUCAD's authoritative institutional email domain(s) with IT.
- Add the production redirect URI and rotate the development client secret.
- Test one account from each role plus one roleless account.
- Enable HTTPS, secure cookies, Redis/Horizon, S3 and SMTP in production.
