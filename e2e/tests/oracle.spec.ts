import { test } from '@playwright/test'
import { assertFixtureOracle } from '../contract/oracle'

test('fixture expectations match the independent conformance oracle', () => {
  assertFixtureOracle()
})
