<div class="bureaucracy">
    <div class="heading">
        <img class="left" src="/caps/css/img/cherubino_black.png"/>
        <h2 class="department">Dipartimento di Matematica — Università di Pisa</h2>
        <h2 class="degree">Corso di Laurea
            <?php echo (strpos($proposal['curriculum'][0]['name'], 'Triennale') !== false) ? 'Triennale — Classe L-35' : 'Magistrale — Classe LM-40'; ?>
        </h2>
        <h2 class="year"><?php
            /* At the moment we do not have the information on the academic
             * year inside the database,so we guess based on the deadline. */
            $year = $proposal['modified']->year;
            $month = $proposal['modified']->month;

            if ($month <= 8)
              $year = $year - 1;

            echo "Anno Accademico " . $year . "/" . ($year + 1);
        ?></h2>
    </div>
    <div class="data">
        <h3 class="curriculum">Curriculum: <?php echo $proposal['curriculum'][0]['name']; ?></h3>
        <h3 class="name">Nome e cognome: <?php echo $proposal['user']['name']; ?></h3>
        <h3 class="number">Matricola: <?php echo $proposal['user']['number']; ?></h3>
        <h3 class="email">Email: </h3>
        <h3 class="telephone">Telefono: </h3>
    </div>
    <div class="plea">
        <p>chiede l'approvazione del seguente Piano di Studio:</p>
    </div>
</div>

<div class="heading--web">
    <h2>Piano di Studi di <?php echo $proposal['user']['name']; ?></h2>
</div>

<?php if($proposal['submitted'] && $proposal['approved']): ?>
<div class="success">
    Il tuo Piano di Studi è stato approvato.
</div>
<?php elseif($proposal['submitted']): ?>
<div class="notice">
    Stampa il tuo Piano di Studi, firmalo e consegnalo in Segreteria Studenti.
</div>
<?php endif; ?>

<div class="notice">
    Se desideri modificarlo manda una mail alla Segreteria Studenti.
</div>

<table>
    <tr>
        <th>Codice</th>
        <th>Nome</th>
        <th>Settore</th>
        <th>Crediti</th>
    </tr>
<?php foreach ($proposal['chosen_exams'] as $chosen_exam): ?>
    <?php
        foreach($exams as $exam) {
            if ($exam['id'] == $chosen_exam['exam_id']) {
                $code = $exam['code'];
                $name = $exam['name'];
                $sector = $exam['sector'];
            }
        }
    ?>
    <tr>
        <td><?php echo $code ?></td>
        <td><?php echo $name ?></td>
        <td><?php echo $sector ?></td>
        <td><?php echo $chosen_exam['credits']; ?></td>
    </tr>
<?php endforeach; ?>
<?php unset($chosen_exam); ?>
<?php foreach ($proposal['chosen_free_choice_exams'] as $exam): ?>
    <tr>
        <td></td>
        <td><?php echo $exam['name']; ?></td>
        <td></td>
        <td><?php echo $exam['credits']; ?></td>
    </tr>
<?php endforeach; ?>
<?php unset($exam); ?>
</table>

<div class="bureaucracy">
    <div class="left">
        <div class="date">Data di presentazione</div><br>
        <div class="examined">Esaminato dal CdS in data</div><br>
        <div class="result">
            Esito: <ul>
                <li>Approvato ☐</li>
                <li>Rifiutato ☐</li>
            </ul>
        </div><br>
        <div class="confirmation">Firma del Presidente</div>
    </div>

    <div class="right">
        <div class="signature">Firma dello studente</div>
    </div>
</div>
