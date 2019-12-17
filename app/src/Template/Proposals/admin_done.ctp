<?php echo $this->element('admin_navigation'); ?>

<h2>Piani di Studio approvati</h2>
<table class="caps-done">
    <tr>
        <th>Nome</th>
        <th>Piano di Studio</th>
        <th>Azioni</th>
    </tr>
<?php foreach ($proposalsApproved as $proposal): ?>
    <tr>
        <td class="caps-admin-proposal-name"><?php echo $proposal['User']['name']; ?></td>
        <td class="caps-admin-proposal-pds">
            <?php
                echo $this->Html->link(
                    $proposal['Curriculum'][0]['name'],
                    array('action' => 'review', $proposal['Proposal']['id'])
                );
            ?>
        </td>
        <td class="caps-admin-proposal-actions">
            <ul class="actions">
                <li>
                    <?php
                        echo $this->Html->link(
                            'Riapri&nbsp;✎',
                            array(
                                'action' => 'reject',
                                $proposal['Proposal']['id']
                            ),
                            array(
                                'escape' => false
                            )
                        );
                    ?>
                </li>
                <li>
                    <?php
                        echo $this->Html->link(
                            'Archivia&nbsp;❄',
                            array(
                                'action' => 'freeze',
                                $proposal['Proposal']['id']
                            ),
                            array(
                                'escape' => false
                            )
                        );
                    ?>
                </li>
            </ul>
        </td>
    </tr>
<?php endforeach; ?>
<?php unset($proposal); ?>
</table>

<?php echo $this->element('pagination'); ?>
