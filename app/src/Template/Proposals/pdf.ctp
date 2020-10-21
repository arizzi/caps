<?php
/**
 * CAPS - Compilazione Assistita Piani di Studio
 * Copyright (C) 2014 - 2020 E. Paolini, J. Notarstefano, L. Robol
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * This program is based on the CakePHP framework, which is released under
 * the MIT license, and whose copyright is held by the Cake Software
 * Foundation. See https://cakephp.org/ for further details.
 */
?>
<style>
    html {
        margin: 0;
    }

    body {
        font-family: "Helvetica";
        font-size: 0.7rem;
        margin: 0.8cm;
    }

    h2, h3, h4 {
        margin: 0;
        padding: 0;
    }

    h2 {
        font-size: 0.8rem;
    }

    h3 {
        font-size: 0.8rem;
    }

    h4 {
        font-size: 0.9rem;
        font-variant: small-caps;
        margin-bottom: 6px;
        margin-top: 6px;
    }

    table.table {
        border-collapse: collapse;
        width: 18cm;
        margin-bottom: 12px;
    }

    .heading {
      margin-bottom: 0.4cm;
    }

    td, th {
        border-bottom: 1px solid grey;
        margin: 0;
        padding: 3px;
        text-align: left;
    }

    th {
        border-bottom: 3px groove grey;
    }

    td.heading {
        padding: 6px;
        padding-right: 0.5cm;
        border: none;
    }

    .bottom {
        margin-top: 1cm;
    }

    .badge {
        display: inline;
        padding: 0.02cm;
        background-color: #eee;
        font-size: 0.5rem;
        font-weight: bold;
        margin-left: 4px;
        border: 1px solid black;
        border-left: ;: 1px solid black;
    }

</style>

<div class="heading">
    <table>
        <tr>
            <td class="heading">
                <img src="data:image/png;base64,<?= base64_encode(file_get_contents($app_path . '../webroot/img/cherubino.png')) ?>" />
            </td>
            <td class="heading">
                <h2 class="department"><?php echo h($settings['department']) ?></h2>
                <h2 class="degree"><?php echo ($proposal['curriculum']['degree']['name']); ?></h2>
                <h2 class="year"><?php
                    /* At the moment we do not have the information on the academic
                     * year inside the database,so we guess based on the deadline. */
                    $year = $proposal['modified']->year;
                    $month = $proposal['modified']->month;

                    if ($month <= 8)
                        $year = $year - 1;

                    echo "Anno Accademico " . $year . "/" . ($year + 1);
                    ?></h2>
            </td>
        </tr>
    </table>


</div>
<div class="data">
    <strong>Curriculum</strong>: <?php echo h($proposal['curriculum']['name']); ?><br>
    <strong>Anno di immatricolazione</strong>: <?= $proposal['curriculum']['academic_year'] ?>/<?= $proposal['curriculum']['academic_year']+1 ?><br>
    <strong>Nome e cognome</strong>: <?php echo h($proposal['user']['name']); ?></strong><br>
    <strong>Matricola</strong>: <?php echo h($proposal['user']['number']); ?><br>
    <strong>Email</strong>: <?= h($proposal['user']['email']) ?><br>
</div>
<div class="plea">
    <p>chiede l'approvazione del seguente Piano di Studio:</p>
</div>

<?php for ($year = 1; $year <= 3; $year++): ?>

    <?php
    $this_year_exams = array_filter($proposal['chosen_exams'],
        function ($e) use ($year) {
            return $e['chosen_year'] == $year;
        });

    $this_year_free_choice_exams = array_filter($proposal['chosen_free_choice_exams'],
        function ($e) use ($year) {
            return $e['chosen_year'] == $year;
        });

    if (max(count($this_year_exams), count($this_year_free_choice_exams)) > 0): ?>
        <div>
            <?php
            echo "<h4>";
            switch ($year) {
                case 1:
                    echo "Primo anno";
                    break;
                case 2:
                    echo "Secondo anno";
                    break;
                case 3:
                    echo "Terzo anno";
                    break;
                default:
                    echo "Anno " . $year;
                    break;
            }
            echo "</h4>";
            $year_credits = 0;
            ?>

            <table class="table">
                <thead>
                <tr>
                    <th>Codice</th>
                    <th>Nome</th>
                    <th>Settore</th>
                    <th>Crediti</th>
                    <th>Gruppo</th>
                </tr>
                </thead>
                <?php foreach ($this_year_exams as $chosen_exam): ?>
                    <?php
                    $exam = $chosen_exam['exam'];
                    $code = $exam['code'];
                    $name = $exam['name'];
                    $sector = $exam['sector'];
                    $year_credits = $year_credits + $chosen_exam['credits'];
                    ?>
                    <tr>
                        <td><?php echo h($code) ?></td>
                        <td><?php echo h($name) ?>
                            <?php if (count($exam['tags']) > 0): ?>
                                <div class="badge">
                                    <?php echo $exam->tagsToString(); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo h($sector) ?></td>
                        <td><?php echo h($chosen_exam['credits']); ?></td>
                        <td><?php
                            $cg = $chosen_exam['compulsory_group'];
                            $ce = $chosen_exam['compulsory_exam'];
                            $cf = $chosen_exam['free_choice_exam'];

                            if ($cg != null) {
                                echo h($cg['group']['name']);
                            }
                            else if ($ce != null) {
                                echo "Obbligatorio";
                            }
                            else if ($cf != null) {
                                echo "A scelta libera";
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php unset($chosen_exam); ?>
                <?php foreach ($this_year_free_choice_exams as $exam): ?>
                    <tr>
                        <td></td>
                        <td><?php echo h($exam['name']); ?></td>
                        <td></td>
                        <td><?php echo $exam['credits']; ?></td>
                        <?php $year_credits = $year_credits + $exam['credits']; ?>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
                <?php unset($exam); ?>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><strong><?php echo $year_credits; ?></strong></td>
                    <td></td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
<?php endfor; ?>

<div class="bottom">
<div class="left">
    <div class="date">Data di presentazione: <?=
        ($proposal['submitted_date'] != null) ?
            $proposal['submitted_date']->setTimezone($Caps['timezone'])->i18nformat('dd/MM/yyyy, HH:mm') : 'non ancora presentato';
        ?></div>
    <?php if ($proposal['state'] == 'approved'): ?>
        <div class="examined">Esaminato in data: <?=
            ($proposal['approved_date'] != null) ?
                $proposal['approved_date']->setTimezone($Caps['timezone'])->i18nformat('dd/MM/yyyy, HH:mm') :
                'data non disponibile'
            ?></div>
        <div class="result">
            Esito: <strong>Approvato</strong>
        </div>
        <br>
        <div class="confirmation"><?= h($settings['approval-signature-text']); ?></div>
    <?php endif; ?>
</div>

<div class="right">
    <div class="signature"><!-- Firma dello studente //--></div>
</div>
</div>
