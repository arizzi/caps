<?php
/**
 * CAPS - Compilazione Assistita Piani di Studio
 * Copyright (C) 2014 - 2021 E. Paolini, J. Notarstefano, L. Robol
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
<!--app id="app"></app-->

<h1>Piano di Studi di <?php echo $proposal['user']['name']; ?></h1>

<?php 
$message = $this->Caps->proposalMessage($proposal);
if ($message != "") {
    echo $this->element('card-start', [ 'border' => $this->Caps->proposalColor($proposal) ]);
    echo $message;
    echo $this->element('card-end'); 
} 
?>

<?= $this->element('card-start'); ?>

<div class="d-flex mb-2">

<?php if ($user['admin'] && $proposal['state'] == 'submitted'): ?>
    <!-- Toolbar per l'amministratore -->
    <a href="<?= $this->Url->build([ 'action' => 'admin_approve', $proposal['id'] ]) ?>">
        <button type="button" class="btn btn-sm btn-success mr-2">
            <i class="fas fa-check"></i> <span class="d-none d-md-inline">Accetta</span>
        </button>
    </a>
    <a href="<?= $this->Url->build([ 'action' => 'admin_reject', $proposal['id'] ]) ?>">
        <button type="button" class="btn btn-sm btn-danger mr-2">
            <i class="fas fa-times"></i> <span class="d-none d-md-inline">Rifiuta</span>
        </button>
    </a>
<?php endif; ?>

<?php if ($proposal['curriculum']['degree']->isSharingEnabled($user)): ?>
    <?php if (($proposal['state'] == 'submitted') && ($proposal['user_id'] == $user['id'] || $user['admin'])): ?>
    <div class="dropdown">
        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">
            Richiedi parere
        </button>
        <div class="dropdown-menu p-3" style="min-width: 450px;">
            <?php
            echo $this->Form->create($proposal_auth, [
                'url' => [
                    'controller' => 'proposals',
                    $proposal['id'],
                    'action' => 'share'
                ]
            ]);
            echo $this->Form->control(
                'email',
                [ 'label' => 'Email' ]);
            echo $this->Form->submit('Richiedi parere');
            echo $this->Form->end();
            ?>
        </div>
    </div>
    <?php endif ?>
<?php endif ?>

    <div class="flex-fill"></div>

    <!-- The following PDF actions are repeated twice: one rendering is for screen md or larger,
         whereas the other handles small screens, and is packed into a dropdown. -->
    <a class="d-none d-md-inline"
       href="<?= $this->Url->build([ 'action' => 'pdf', $proposal['id'] ]) ?>">
        <button type="button" class="btn btn-sm btn-primary mr-2">
            <i class="fas fa-file-pdf"></i> Scarica come PDF
        </button>
    </a>
    <a class="d-none d-md-inline"
       href="<?= $this->Url->build([ 'action' => 'pdf', '?' => ['show_comments' => True], $proposal['id']]) ?>">
        <button type="button" class="btn btn-sm btn-primary">
            <i class="fas fa-file-pdf mr-2"></i>PDF inclusi i commenti
        </button>
    </a>

    <div class="dropdown d-md-none">
        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown">
            <i class="fas fa-file-pdf"></i>
        </button>
        <div class="dropdown-menu p-3">
            <a class="dropdown-item" href="<?= $this->Url->build([ 'action' => 'pdf', $proposal['id'] ]) ?>">
                <i class="fas fa-file-pdf"></i> Scarica come PDF
            </a>
            <a class="dropdown-item" href="<?= $this->Url->build([ 'action' => 'pdf', 'show_comments' => True, $proposal['id']]) ?>">
                <i class="fas fa-file-pdf"></i> PDF inclusi i commenti
            </a>
        </div>
    </div>
</div>

    <table class="table">
        <tr>
            <th>Stato</th>
            <td><?= $this->Caps->badge($proposal); ?></td>
        </tr>
        <tr>
            <th>Corso di Laurea</th>
            <td><?= h($proposal['curriculum']['degree']['name']) ?></td>
        </tr>
        <tr>
            <th>Curriculum</th>
            <td><?= h($proposal['curriculum']['name']) ?></td>
        </tr>
        <tr>
            <th>Anno di immatricolazione</th>
            <td><?= $proposal['curriculum']['degree']->academic_years() ?></td>
        </tr>
    </table>
<?= $this->element('card-end'); ?>

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

  $header = "";

    if (max(count($this_year_exams), count($this_year_free_choice_exams)) > 0): ?>
    <div>
    <?php
        switch ($year) {
            case 1:
                $header = "Primo anno";
                break;
            case 2:
                $header = "Secondo anno";
                break;
            case 3:
                $header = "Terzo anno";
                break;
            default:
                $header = "Anno " . $year;
                break;
        }
        $year_credits = 0;
?>

<?= $this->element('card-start', [ 'header' => $header ]); ?>

<div class="table-responsive-md">
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
            <div class="badge badge-secondary badge-sm">
                <?php echo h($exam->tagsToString()) ?>
            </div>
        <?php endif; ?>
        </td>
        <td><?php echo h($sector) ?></td>
        <td><?php echo $chosen_exam['credits']; ?></td>
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
</div>
<?= $this->element('card-end'); ?>

<?php endif; ?>
<?php endfor; ?>

<?= $this->element('card-start', [ 'header' => 'Allegati e commenti' ]) ?>
    <?php
      $visible_attachments = array_filter(
          $proposal['attachments'],
          function ($a) use ($user, $secrets) { return $user && $user->canViewAttachment($a, $secrets); }
      );

      $authorizations = $proposal->auths;

      // Construct an array with the attachments and the authorizations, and sort it
      $attachments_and_auths = array_merge($visible_attachments, $authorizations);
      usort($attachments_and_auths, function ($a, $b) {
          return $a->created->getTimestamp() - $b->created->getTimestamp();
      });

      $events_count = count($attachments_and_auths);
     ?>

    <p>
    <?php if ($user != $proposal->user): ?>
    Lo studente può vedere i commenti e gli allegati. <br />
    <?php endif ?>
    <ul class="attachments">
    <?php foreach ($attachments_and_auths as $att): ?>
    <?php if ($att instanceof \App\Model\Entity\Attachment): ?>
        <?= $this->element('attachment', [
                'attachment' => $att,
                'controller' => 'attachments',
                'name' => $att->filename == null ? 'Commento' : 'Allegato'
            ])
        ?>
    <?php else: ?>
        <li class="card border-left-warning mb-2">
            <div class="card-body p-1">
                Richiesta di parere inviata a <strong><?= $att['email'] ?></strong> <?php if ($att['created'] != null) {
                    ?>  — <?php
                    echo $this->Caps->formatDate($att['created']);
                }
                ?>
            </div>
        </li>
    <?php endif ?>
    <?php endforeach ?>
    </ul>
    <?php if ($user && $user->canAddAttachment($proposal, $secrets)): ?>

    <button type="button" class="dropdown-toggle btn btn-primary btn-sm" data-toggle="collapse" data-target="#add-attachment">
        Inserisci un nuovo allegato o commento
    </button>
    <div class="collapse my-3 mx-0" id="add-attachment">
        <div class="card border-left-primary p-3">
        <?php
        echo $this->Form->create(null, [
            'url' => ['controller' => 'attachments', 'action' => 'add'],
            'type' => 'file'
        ]);
        ?>

        <div class="form-group">
            <?php echo $this->Form->textarea('comment'); ?>
        </div>
        <div class="form-group">
            <?php echo $this->Form->file('data'); ?>
        </div>
        <?php echo $this->Form->hidden('proposal_id', ['value' => $proposal['id']]); ?>
        <?php echo $this->Form->submit('Aggiungi commento e/o allegato'); ?>
        <?php echo $this->Form->end(); ?>
        <?php endif; ?>
        </div>
    </div>
<?= $this->element('card-end'); ?>

