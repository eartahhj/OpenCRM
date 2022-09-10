CREATE TABLE utenti(
id SERIAL PRIMARY KEY,
username VARCHAR(100) NOT NULL UNIQUE,
password_hash VARCHAR(255) NOT NULL,
password_salt VARCHAR(255) NOT NULL,
email VARCHAR(255) NOT NULL,
ultimo_accesso TIMESTAMP NOT NULL DEFAULT NOW(),
livello INTEGER NOT NULL DEFAULT 1,
token VARCHAR(255) NULL,
data_creazione TIMESTAMP NOT NULL DEFAULT NOW(),
data_modifica TIMESTAMP NOT NULL DEFAULT NOW(),
nome TEXT NOT NULL,
cognome TEXT NOT NULL,
attivo BOOLEAN NOT NULL DEFAULT 't',
timesheet_abilitato BOOLEAN NOT NULL DEFAULT false
costo NUMERIC
);

INSERT INTO utenti (id, username, password_hash, password_salt, email, livello, nome, cognome, attivo, timesheet_abilitato)
VALUES(1, 'admin', 'hash', 'salt', 'email@dominio.it', 3, 'Nome', 'Cognome', 't', 't');

CREATE TABLE ruoli_utente (
	id SERIAL PRIMARY KEY,
	nome VARCHAR(100),
	data_creazione TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
	data_modifica TIMESTAMP WITHOUT TIME ZONE,
	utente_creazione INTEGER,
	utente_modifica INTEGER
);

INSERT INTO public.ruoli_utente(nome)
VALUES ('Direzione');

INSERT INTO public.ruoli_utente(nome)
VALUES ('Amministrazione');

INSERT INTO public.ruoli_utente(nome)
VALUES ('Project Management');

INSERT INTO public.ruoli_utente(nome)
VALUES ('Web development');

INSERT INTO public.ruoli_utente(nome)
VALUES ('Marketing e comunicazione');

INSERT INTO public.ruoli_utente(nome)
VALUES ('Commerciale');

INSERT INTO public.ruoli_utente(nome)
VALUES ('Sistemista');

INSERT INTO public.ruoli_utente(nome)
VALUES ('Collaboratore esterno');

INSERT INTO public.ruoli_utente(nome)
VALUES ('Stagista/Tirocinante');

CREATE TABLE link_utenti_ruoli
(
	id_utente INTEGER,
	id_ruolo INTEGER
);

INSERT INTO link_utenti_ruoli (id_utente, id_ruolo) VALUES(1, 4);

CREATE TABLE notifiche (
	id SERIAL,
	testo TEXT,
	letta BOOLEAN DEFAULT 'f',
	tipologia INTEGER,
	id_utente INTEGER,
    timestamp_creazione TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW(),
    timestamp_modifica TIMESTAMP WITHOUT TIME ZONE
);
