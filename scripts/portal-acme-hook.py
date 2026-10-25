#!/usr/bin/env python3
"""HTTP-01 no Nginx existente, sem copiar chaves TLS para fora da VPS."""
import os
import re
import subprocess

token = os.environ.get("CERTBOT_TOKEN", "")
validation = os.environ.get("CERTBOT_VALIDATION", "")
if not re.fullmatch(r"[A-Za-z0-9_-]+", token) or not validation:
    raise SystemExit("Desafio ACME inválido")
directory = "/var/www/certbot/.well-known/acme-challenge"
subprocess.run(["docker", "exec", "cetsadp-nginx", "mkdir", "-p", directory], check=True)
subprocess.run(
    ["docker", "exec", "-i", "cetsadp-nginx", "sh", "-c",
     'umask 022; cat > "$1"', "sh", directory + "/" + token],
    input=validation.encode(), check=True,
)
