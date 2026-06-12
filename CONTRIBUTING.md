# How to Contribute

Thank you for considering contributing to edfolio.

## Getting Started

1.  Fork the Repository and Create a Branch
2.  Commit Changes
3.  Submit a Pull Request

## Local Quality Gates

Enable the repository hooks once per clone:

```bash
./bin/setup-git-hooks.sh
```

Run the standard validation suite before pushing:

```bash
./bin/validate.sh
```

That path mirrors the repository pre-push hook and currently covers PHPUnit, ESLint for `resources/js`, frontend build validation, and Docker Compose config checks.

Run audit-only dependency checks when reviewing dependency changes or triaging advisories:

```bash
npm run audit:npm
npm run audit:npm:production
npm run audit:composer
```

Do not use `security-check` or `security-check-force` unless you intentionally want `npm audit fix` to update dependency manifests or lockfiles.

`npm run audit:composer` requires Composer 2.4 or newer.

## Credit

If you are adding a 3rd party template for frontend, be sure to add any required credit or copyright notice where it is applicable.

## Contact
If you have any questions, please feel free to create an issue in the repository.
