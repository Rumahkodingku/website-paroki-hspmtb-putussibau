/**
 * Commit message rules.
 *
 * The conventional config and nothing else. Its defaults already accept every
 * message this repository has ever written: header-max-length is 100 and the
 * longest subject in the Phase 01 history is 83 characters. Adding rules on top
 * would only narrow a gate that is currently passing on real work.
 *
 * ESM because package.json declares "type": "module". A CommonJS
 * module.exports here is a load error at commit time rather than at build time,
 * so it would fail on somebody's first commit instead of in CI.
 *
 * @see docs/DECISIONS.md D-28
 */
export default {
    extends: ['@commitlint/config-conventional'],
};
