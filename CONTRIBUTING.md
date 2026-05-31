# Contributing

Thanks for considering a contribution. This guide covers setup, the development loop, commit style, and pull requests.

## Local setup

```bash
git clone https://github.com/blessedzulu/nativephp-admob.git
cd nativephp-admob
composer install
composer test
composer lint
```

If you want to exercise the plugin against a real Laravel app, register it as a path repository in that app's `composer.json`:

```json
{
    "repositories": [
        {"type": "path", "url": "../nativephp-admob"}
    ]
}
```

Then `composer require blessedzulu/nativephp-admob:*@dev`. PHP changes pick up immediately. Native code changes require a fresh `php artisan native:run`. Manifest changes require `php artisan native:install --force`.

## Branching and pull requests

- Fork the repository, branch from `main`.
- One concern per pull request. Smaller PRs review faster.
- Update `CHANGELOG.md` under `[Unreleased]` describing what changed.
- Make sure `composer test` and `composer lint` both pass before opening the PR.
- The PR template includes a checklist - tick everything that applies.

## Commit messages

Light and short. Use [Conventional Commits](https://www.conventionalcommits.org/) prefixes:

- `feat:` new functionality
- `fix:` bug fix
- `docs:` documentation only
- `test:` test changes
- `refactor:` no behaviour change
- `chore:` housekeeping
- `ci:` workflow or build changes

Examples:

```
feat: banner ad load + show
fix: rewarded ad reward callback fires twice on Android
docs: clarify UMP opt-out in README
```

One line is plenty. A body is only needed when the why is non-obvious.

## What this project does not accept in commits or PRs

- AI co-authorship attribution lines (`Co-Authored-By: Claude`, `Co-Authored-By: ChatGPT`, etc.)
- "Generated with [tool]" footers
- Emoji in commit messages (subject or body)

Use AI tools as much as you want during development - just don't add attribution to the commit graph or PR body.

## Reporting bugs

Open an issue using the bug template. Include:

- AdMob ad format affected (banner / interstitial / rewarded / etc.)
- NativePHP Mobile version
- Platform (iOS or Android) and OS version
- Test or production ad units
- Steps to reproduce, expected vs actual

## Requesting features

Open an issue using the feature template. Describe the use case before the proposed API - it helps reviewers spot simpler alternatives.

## Good first issues

Issues labelled `good first issue` are scoped for newcomers. They typically touch one file, have clear acceptance criteria, and reference an existing pattern in the codebase to follow.

## Security

If you find a security issue, please email the maintainers instead of opening a public issue. See [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) for contact details.
