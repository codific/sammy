#!/bin/bash
echo "clone or pull OWASP-SAMM model repository"
./scripts/clone_owasp_samm.sh

echo "Syncing from OWASP SAMM repo..."
php bin/console app:sync-from-owasp-samm 1