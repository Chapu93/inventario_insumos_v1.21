#!/usr/bin/env bash
set -euo pipefail

CSS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../public/css" && pwd)"

printf "%-25s | %8s | %10s\n" "Archivo" "Lineas" "!important"
printf "%s\n" "--------------------------------------------------------"

total_lines=0
total_important=0

for file in "$CSS_DIR"/*.css; do
    [ -f "$file" ] || continue
    fname="$(basename "$file")"
    lines=$(wc -l < "$file")
    # Ignorar comentarios multilínea (/* ... */) al contar !important
    important=$(perl -0777 -pe 's/\/\*.*?\*\///gs' "$file" | grep -o "!important" | wc -l || true)
    
    total_lines=$((total_lines + lines))
    total_important=$((total_important + important))
    
    printf "%-25s | %8d | %10d\n" "$fname" "$lines" "$important"
done

printf "%s\n" "--------------------------------------------------------"
printf "%-25s | %8d | %10d\n" "TOTAL" "$total_lines" "$total_important"
printf "\n"

# Análisis de tokens CSS
python3 -c "
import os, re
css_dir = '$CSS_DIR'
css_files = [os.path.join(css_dir, f) for f in os.listdir(css_dir) if f.endswith('.css')]
all_css = ''
for f in css_files:
    with open(f, 'r', encoding='utf-8') as fh:
        c = fh.read()
        c = re.sub(r'/\*.*?\*/', '', c, flags=re.DOTALL)
        all_css += c + '\n'

tokens_def = set(re.findall(r'(--sitia-[a-zA-Z0-9_-]+)\s*:', all_css))
tokens_var = set(re.findall(r'var\(\s*(--sitia-[a-zA-Z0-9_-]+)', all_css))
unreferenced = sorted(tokens_def - tokens_var)

print(f'Tokens SITIA definidos: {len(tokens_def)}')
print(f'Tokens SITIA referenciados con var(): {len(tokens_var)}')
print(f'Tokens SITIA sin referencia var(): {len(unreferenced)}')
if unreferenced:
    for t in unreferenced:
        print(f'  - {t}')
"
