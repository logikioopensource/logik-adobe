#!/bin/bash

echo "Running Unit Tests..."
docker compose run --rm deploy magento-command dev:tests:run unit -c'--testsuite="Logik_LogikSettings Unit Tests"'

echo "Running Integration Tests..."
docker compose run --rm deploy magento-command dev:tests:run integration -c'--testsuite="Logik Settings Integration Tests"' 