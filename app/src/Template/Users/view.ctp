<h2>Piani di studio di <?php echo $user['name']; ?></h2>

<table class='caps-todo'>
  <tr>
    <th>Curriculum</th>
    <th>Ultima modifica</th>
    <th>Stato</th>
    <th>Azioni</th>
  </tr>
<?php
  $num_proposals = 0;

  foreach ($proposals as $proposal) {
    // We keep track of the number of proposals, since $proposals is not an array
    // but an opaque query object, and does not report the number of results beforehand.
    $num_proposals++;

    // Compute the status
      switch ($proposal['state']) {
          case 'draft':
              $status = 'Bozza';
              break;
          case 'submitted':
              $status = 'Sottomesso';
              break;
          case 'approved':
              $status = "Approvato ✓";
              break;
          case 'rejected':
              $status = 'Rigettato';
              break;
      }
    ?>
      <tr>
       <td><?php echo $proposal['curriculum']['name']; ?>
       </td>
       <td><?php echo $proposal['modified']; ?></td>
       <td><?php echo $status; ?></td>
       <td>
           <?php
           switch ($proposal['state']) {
               case "draft":
                   echo $this->Html->link('Modifica', [
                       'controller' => 'proposals',
                       'action' => 'add',
                       $proposal['id'] ]);
                   break;
               case "submitted":
               case "approved":
               case "rejected":
                   echo $this->Html->link('Visualizza', [
                       'controller' => 'proposals',
                       'action' => 'view',
                       $proposal['id'] ]);
         }

         ?>
       </td>
      </tr>
    <?php
  }
?>
</table>

<?php if ($num_proposals == 0) {  ?>
    <p>Al momento non è stato presentato alcun piano di studio.</p>
<?php
}
?>

<?php if ($owner['id'] == $user['id']): ?>
  <!-- Pulsante di creazione di un nuovo piano, visibile solo per il proprietario,
       e non se qualcuno sta visualizzando il profile come amministratore. //-->
  <div class="caps-admin-add">
    <ul>
      <li>
        <?php echo $this->Html->link('Nuovo piano di studi', [
            'controller' => 'proposals',
            'action' => 'add'
        ]);
        ?>
      </li>
    </ul>
  </div>
<?php endif; ?>
