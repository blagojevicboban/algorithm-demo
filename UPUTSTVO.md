# Demo sajt algorithm.tsp.edu.rs – uputstvo

U ovom folderu su:
- `algoritam-demo.mbz`: demo kurs na engleskom jeziku (`ALGORITHM-DEMO`), sa 5 stranica, kvizom i forumom;
- `algoritam-demo-sr.mbz`: demo kurs na srpskom jeziku (`ALGORITHM-DEMO-SR`), sa 5 stranica, kvizom i forumom;
- `demo.php`: skripta za prvo podešavanje (`setup`) i noćno vraćanje oba kursa (`reset`).

Pluginovi su u `C:\tsp\dist`: `tiny_algorithm-*.zip`, `filter_algorithm-*.zip`, `qtype_algorithm-*.zip`.

## 1. Moodle struktura na serveru

- Kod je u `/home/algoritam/public_html/moodle`.
- Podaci idu u `/home/algoritam/moodledata`.
- U Virtualmin-u, **Server Configuration → Website Options**: document root je `public_html/moodle/public`.
- U **PHP Options** izabran je PHP 8.3 i postavljen `max_input_vars = 5000`.

## 2. Pluginovi

Kao administrator idi na **Site administration → Plugins → Install plugins**. Ako izlazi nova verzija, otpremi ZIP-ove ovim redom:
1. `tiny_algorithm-*.zip`
2. `filter_algorithm-*.zip`
3. `qtype_algorithm-*.zip`

Filter ne moraš da uključuješ ručno, to radi skripta.

## 3. Fajlovi za demo (ažuriranje)

Otpremi 3 fajla u `/home/algoritam/public_html/moodle`, pored `config.php`:
- `demo.php`
- `algoritam-demo.mbz`
- `algoritam-demo-sr.mbz`

Primer preko SCP:
```bash
scp dist/demo-site/demo.php dist/demo-site/algoritam-demo.mbz dist/demo-site/algoritam-demo-sr.mbz algoritam@algorithm.tsp.edu.rs:/home/algoritam/public_html/moodle/
```

## 4. Reset / primena kurseva

Na serveru pokreni komandu:
```bash
ssh algoritam@algorithm.tsp.edu.rs
cd /home/algoritam/public_html/moodle
php demo.php reset
```

Skripta radi sledeće:
- Vraća oba demo kursa iz arhiva:
  - `ALGORITHM-DEMO` – *Algorithms with flowcharts: demo (English)*
  - `ALGORITHM-DEMO-SR` – *Algoritmi sa dijagramima toka: demo (srpski)*
- U Sekciju 0 oba kursa postavlja banere za prebacivanje jezika (cross-link).
- Upisuje demo naloge (`student` i `teacher`, lozinka `Demo-2026`) u oba kursa.
- Ažurira naslovnu stranu sajta sa dva hero dugmeta (`🚀 Open Demo Course (English)` i `🇷🇸 Demo kurs (srpski)`).

## 5. Stalni cron poslovi

| Posao | Command | Kada |
|---|---|---|
| Moodle cron | `/usr/bin/php /home/algoritam/public_html/moodle/admin/cli/cron.php >/dev/null` | svakog minuta |
| Noćni reset | `/usr/bin/php /home/algoritam/public_html/moodle/demo.php reset >/dev/null` | svakog dana u 3:30 |

Reset svake noći vraća oba kursa iz backup-a u iste kurseve, sa istim adresama. Brišu se postovi, pokušaji kviza i izmene, a nalozi i upisi ostaju.

## 6. Provera

- Otvori sajt https://algorithm.tsp.edu.rs/ kao gost (dugme **Log in as guest**).
- Sa naslovne strane otvori i engleski i srpski kurs preko hero dugmadi.
- U zaglavlju sekcije 0 proveri link za prebacivanje na drugi jezik.
- Na vežbama klikni **Edit**, pa **Test**.
