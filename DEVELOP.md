# Informazioni utili per sviluppatori

## Ambiente preconfigurato in docker

Il modo più semplice per avviare un server di sviluppo è quello di utilizzare 
un container docker pre configurato. Basta dare il comando:

```bash
./start-dev-server.sh
```

Una volta avviato il container (per il primo avvio ci può volere più tempo) il server viene esposto all'indirizzo
http://localhost:8765

L'interfaccia di *CakePHP* risulterà quindi accessibile tramite il comando
```bash
sudo docker exec -it caps bin/cake
```
Il database sarà invece accessibile tramite il comando
```
sudo docker exec -it caps-db mysql caps -u caps -p
```
la password del database è `secret` (specificata in docker/caps.env).

I files del database persistono nella directory `docker/database`

Al primo utilizzo è necessario inserire un utente amministratore per accedere 
al servizio. Per creare uno username `admin` con password `admin` dare 
il comando:
```bash
sudo docker exec -it caps bin/cake grant-admin admin --force --password admin
```

## Preparazione dell'ambiente di sviluppo

CAPS è sviluppato utilizzando il framework [CakePHP](https://cakephp.org) per il backend, 
ed alcune librerie Javascript per (parte) del frontend. Per un ambiente di sviuppo locale
è necessario avere a disposizione PHP, NPM, e un database (Sqlite3 può andare bene). 

## Installazione
```bash
apt install composer npm
apt install php-mbstring php-intl php-xml php-sqlite3 php-mysql php-zip php-ldap php-gd
apt install sqlite3  # for development
cd frontend/
  npm ci
  npm run test # run js unit tests
  npm run deploy # compiles js and css files
  npm run deploy:dev # as above but for development
cd ..
cd backend
  composer install # installa pacchetti PHP
  bin/cake migrations migrate # crea il database
  vendor/bin/phpunit # esegue test
  bin/cake server # fai partire il server
cd ..
```
## Configurazione di CakePHP

Per utilizzare un server LDAP con certificato SSL non valido, ad esempio perchè inoltrato
tramite una porta locale, è necessario modificare la variabile d'ambiente ```CAPS_VERIFY_CERT``` a false (si trovano più dettagli sotto). Ovviamente, questa configurazione non 
è ideale in produzione. 
Per lo sviluppo in locale, se non si vuole configurare LDAP, è possibile creare gli utenti e definire le password direttamente 
nel database.

Se un utente accede tramite LDAP è possibile renderlo amministratore con il comando
```bash
bin/cake grant-admin username
```
Una volta che è presente il primo amministratore, gli altri possono essere creati
tramite interfaccia web. 

Se non si vuole configurare l'autenticazione LDAP si possono inserire le password localmente nel database.
Ad esempio per creare un utente ```admin``` con password ```secret```:
```bash
bin/cake grant-admin --force --password secret admin
```
## Inoltro dell'LDAP in locale

Per utilizzare un server LDAP disponibile in remoto (ad esempio '''idm2.unipi.it''' sulla macchina '''caps.dm.unipi.it''')
in locale, va inoltrata la porta tramite SSH:
```bash
ssh -L 1636:idm2.unipi.it:636 utente@caps.dm.unipi.it
```
e poi vanno definite le seguenti variabili d'ambiente, ad esempio:
```bash
export CAPS_LDAP_URI=ldaps://127.0.0.1:1636/
export CAPS_LDAP_BASE=ou=people,dc=unipi,dc=it
export CAPS_VERIFY_CERT=false
```

Questa procedura viene gestita in automatico dall'immagine [Docker](docker/README.md). 

## Creazione files HTML e JS

Il template, basato su SB-Admin-2, (CSS e JS) si trova nella cartella ```frontend```. 
Per compilare i file JS e CSS è necessario entrare nella cartella ed usare ```npm```. 

```bash
cd frontend/
npm ci
npm run test # run js unit tests
npm run deploy # compiles js and css files
npm run deploy:dev # as above but for development
```

Dopo la prima compilazione, può essere conveniente usare il comando 
per ricompilare automaticamente i file JS e SCSS quando vengono modificati:

```
npm run watch  
npm run watch:dev
``` 

I file sorgente si trovano rispettivamente 
nelle cartelle ```frontend/scss``` e ```frontend/src```.

Il comando ```deploy``` esegue ```npm run build``` e ```npm run install``` che compilano e 
copiano i file CSS e JS all'interno di ```../app/webroot/```, rispettivamente. Per comodità, i file
già compilati sono inclusi nel repository. 

## Branching model
Utilizziamo il *branching model* descritto qui: https://nvie.com/posts/a-successful-git-branching-model/ in particolare il branch *master* deve poter andare immediatamente in produzione mentre le modifiche non completamente testate andranno nel branch *develop*

```bash
cd app
git checkout develop
bin/cake migrations migrate # Crea o aggiorna il database
vendor/bin/phpunit # run unit tests
vendor/bin/phpunit --filter testLoginPage # run a single test
tail -f logs/*.log # display error messages 
bin/cake server & # run a development server
```

## Upgrade da CAPS < 1.0.0

Per importare un dump vecchio del database (di CAPS < 1.0.0) è necessario prima migrare ad una versione
compatibile, e poi effettuare il resto delle migrazioni. Ad esempio:
```bash
bin/cake migrations migrate -t 20191217155946
sqlite3 caps.sqlite < dump.sql
bin/cake migrations migrate
```

## Struttura dati

    Attachment [attachments]
        id
        filename
        user -> User [user_id]
        proposal -> Proposal [proposal_id]
        data
        mimetype
        comment
        created

    ChosenExam [chosen_exams]
        id
        credits
        chosen_year
        exam -> Exam [exam_id]
        proposal -> Proposal [proposal_id]
        compulsory_group -> CompulsoryGroup [compulsory_group_id] (*) (!) 
        compulsory_exam -> CompulsoryExams [compulsory_exam_id] (*) (!) 
        free_choice_exam -> FreeChoiceExam [free_choice_exam_id] (*) (!) 
        (*) uno solo dei tre puo' essere non null: indica la corrispondenza dell'esame nel curriculum
        (*) se tutti e tre sono null vuol dire che l'esame non era previsto nel curriculum

    ChosenFreeChoiceExam [chosen_free_choice_exams]
        id
        name
        credits
        chosen_year
        proposal -> Proposal [proposal_id]

    CompulsoryExam [compulsory_exams]
        id
        year
        position
        exam -> Exam [exam_id]
        curriculum -> Curriculum [curriculum_id]

    CompulsoryGroup [compulsory_groups]
        id
        year
        position
        group -> Group [group_id]
        curriculum -> Curriculum [curriculum_id]

    Curriculum [curricula]
        id
        name
        notes
        degree -> Degree [degree_id]
        proposals <- Proposals 
        free_choice_exams <- FreeChoiceExam
        compulsory_exams <- CompulsoryExam
        compulsory_groups <- CompulsoryGroup

    Degree [degrees]
        id
        name
        academic_year
        years
        enabled
        enable_sharing
        approval_confirmation
        rejection_confirmation
        submission_confirmation
        approval_message
        rejection_message
        submission_message
        free_choice_message
        <- Curricula
        <- Group

    Documents
        id       
        filename 
        owner_id 
        user_id  
        data     
        created  
        comment  
        mimetype 

    Exam [exams]
        id
        name
        code
        sector
        credits
        <-> Group [exams_groups]

    FreeChoiceExam [free_choice_exams]
        id
        year
        position
        curriculum -> Curriculum [curriculum_id]
        group -> Group [group_id, NULL]

    Form [forms]
        id
        form_template -> FormTemplate
        user > User
        state
        date_submitted
        date_managed
        data

    FormAttachment [form_attachments]
        id
        filename
        user -> User [user_id]
        form -> Form [form_id]
        data
        mimetype
        comment
        created

    FormTemplate [form_templates]
        id
        name
        text
        enabled
        notify_emails

    Group [groups]
        id
        degree -> Degree [degree_id]
        name
        <-> Exam [exams_groups]

    ProposalAuth [proposal_auths]
        id
        email
        secret
        created
        proposal -> Proposal [proposal_id]

    Proposal
        id
        modified (date)
        state in ['draft','submitted','approved','rejected']
        submitted_date (date)
        approved_date (date)
        user -> User [user_id]
        curriculum -> Curriculum [curriculum_id]
        <- ChosenExam
        <- ChosenFreeChoiceExam
        <- ProposalAuth
        <- Attachment

    Settings [settings]
        id
        field
        value
        fieldtype

    Tag [tags]
        id
        name
        <-> Exam [tags_exams]

    User [users]
        id
        username
        name
        number
        givenname
        surname
        email
        admin (bool)
        password (encrypted)

(!) nel database manca il constraint!!
