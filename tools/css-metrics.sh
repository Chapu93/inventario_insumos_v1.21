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
    important=$(grep -o "!important" "$file" | wc -l || true)
    
    total_lines=$((total_lines + lines))
    total_important=$((total_important + important))
    
    printf "%-25s | %8d | %10d\n" "$fname" "$lines" "$important"
done

printf "%s\n" "--------------------------------------------------------"
printf "%-25s | %8d | %10d\n" "TOTAL" "$total_lines" "$total_important"
