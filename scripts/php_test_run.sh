#!/bin/bash
echo "Running All Tests"
php vendor/bin/phpunit --testsuite repository,service,entity,functional
#echo "Running Service Tests"
#php vendor/bin/phpunit --testsuite service
#echo "Running Entity Tests"
#php vendor/bin/phpunit --testsuite entity
#echo "Running Functional Tests"
#php vendor/bin/phpunit --testsuite functional