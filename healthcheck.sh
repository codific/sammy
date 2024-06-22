#!/bin/bash
curl --insecure --fail --silent --show-error http://localhost/api/health && exit 0 || echo 'Fail' && exit 1