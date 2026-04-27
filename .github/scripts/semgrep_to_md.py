#!/usr/bin/env python3
"""Fusionne plusieurs rapports Semgrep JSON en un audit Markdown unique.

Usage : python3 semgrep_to_md.py semgrep-code.json semgrep-containers.json
"""

import json, sys
from collections import Counter
from datetime import datetime, timezone

def badge(sev):
    return {"ERROR":"[CRITIQUE]","WARNING":"[MAJEUR]","INFO":"[MINEUR]"}.get(sev, f"[{sev}]")

def load(path):
    try:
        with open(path, encoding="utf-8") as fh: return json.load(fh)
    except FileNotFoundError: return {"results": [], "errors": []}

def main(paths):
    all_results, all_errors, by_source = [], [], {}
    for p in paths:
        d = load(p)
        r = d.get("results", [])
        all_results += r
        all_errors += d.get("errors", [])
        by_source[p] = len(r)

    now = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M UTC")
    sev = Counter(r.get("extra",{}).get("severity","INFO") for r in all_results)
    verdict = "BLOCKED" if sev.get("ERROR",0) else "PASS"

    out = [f"# Audit sécurité Semgrep — {now}", "",
           f"**Verdict** : `{verdict}`", "",
           "## Synthèse par sévérité", "",
           f"- Total : **{len(all_results)}**",
           f"- ERROR (critiques) : **{sev.get('ERROR',0)}**",
           f"- WARNING (majeurs) : **{sev.get('WARNING',0)}**",
           f"- INFO (mineurs) : **{sev.get('INFO',0)}**", "",
           "## Synthèse par source", ""]
    for src, n in by_source.items(): out.append(f"- `{src}` → {n} findings")
    out.append("")

    if all_results:
        out += ["## Détail", ""]
        for r in sorted(all_results, key=lambda x: {"ERROR":0,"WARNING":1,"INFO":2}.get(
                x.get("extra",{}).get("severity","INFO"), 3)):
            s = r.get("extra",{}).get("severity","INFO")
            meta = r.get("extra",{}).get("metadata",{})
            out += [f"### {badge(s)} `{r.get('check_id')}`", "",
                    f"- Fichier : `{r.get('path','?')}:{r.get('start',{}).get('line','?')}`",
                    f"- CWE : {meta.get('cwe','n/a')}",
                    f"- OWASP : {meta.get('owasp','n/a')}",
                    "",
                    f"> {r.get('extra',{}).get('message','').strip()}", ""]

    print("\n".join(out))
    return 0 if verdict == "PASS" else 2

if __name__ == "__main__":
    sys.exit(main(sys.argv[1:] or ["semgrep.json"]))
