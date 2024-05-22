# Emarsys Client
This is a simple client which acts as a wrapper around the Emarsys API, and allows us to perform common operations against Emarsys abstracted away from the actual API requests.

Originally, this was a fork of the Emarsys client open-sourced by Snowcap. However, they have long since dissolved, and as such upstream is effectively abandoned.

With this in mind Mention Me (the Iris team) maintain and update this client for use in the monolith.

## Usage
The Emarsys client is included via Composer, and can be found in the monolith's [`composer.json`](https://github.com/mention-me/MentionMe/blob/main/composer.json) file.

At the time of writing, our usage of the Emarsys client is limited to one single service in the monolith, called [`EmarsysService`](https://github.com/mention-me/MentionMe/blob/main/src/Nora/EmarsysBundle/EmarsysService.php).

It's main purposes are to: 
1. Fetch data for a contact by email (or ID).
2. Fetch lists of running campaigns.
3. Fetch 'Settings' for the Emarsys account.

### Divergence

Over time, in order to keep pace with modern practices, this client has diverged quite substantially from the original upstream client.

The most notable differences are:
1. Official support for PHP 8.2 and above
2. Support for more API endpoints (tailored to our use cases)
3. PSR 4 namespacing
4. Full CI process and many more tests (both unit and integration)

## Testing

Although not extensive, theres a number of tests which cover most of the main use cases we have for the Emarsys client.

Tests are broken down into two main categories:
1. **Unit** - These test the individual methods of the client, are located in the `tests/Unit` directory, and always mock Emarsys API responses (where required).
2. **Integration** - These test the client's ability to interact with the Emarsys API, are located in the `tests/Integration` directory, and require a valid Emarsys API key to run.

The unit tests are much more extensive than the integration tests, particularly in the context of testing responses from the Emarsys API, because the integration tests
are more prone to non-deterministic failures, because they **must** use a sandbox environment which we cannot control the state of (i.e. fixtures).

### Manual Testing

Outside of the automated tests, the client can be manually tested in the monolith.

This can either be **before** or **after** tagging a new release of the client - if its before, the commit hash should be what is required.

To require a particular **commit** of the Emarsys client, the following command can be run in the monolith's root directory:
```bash
composer require mention-me/Emarsys:dev-<branch name>#<commit hash>
```

To require a particular **version** of the Emarsys client, the following command can be run in the monolith's root directory:
```bash
composer require mention-me/Emarsys:^v<version number>
```

## Releases

You can see past releases of this client (since we took over maintenance) on the [releases page](https://github.com/mention-me/Emarsys/releases).

### Tagging New Releases

The monolith uses Composer to manage **tagged releases** of the Emarsys client. This is to ensure reproducible builds of the monolith.

New tagged releases _will not_ be picked up, and bumped, by Dependabot. Which means any new changes that need to be released to the monolith require a manual PR to be raised.

To release a new version of the Emarsys client, follow these steps:
- Raise a PR to the client ([example](https://github.com/mention-me/Emarsys/pull/94)).
- Merge these changes into `master`, once tests have passed, reviews are complete, and the PR is approved.
- Tag a new release following Semver versioning (e.g. [`v1.8.0`](https://github.com/mention-me/Emarsys/pull/94)).
- Raise a PR in the monolith to update the Emarsys client to the new version ([example](https://github.com/mention-me/MentionMe/pull/18800)).

**Note:** For internal dependency bumps (i.e. merging Dependabot PRs to development dependencies) you _do not_ need to tag a new release. Only changes that need to be reflected in the monolith require a new release.