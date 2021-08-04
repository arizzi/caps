<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;

class DuplicateDegrees extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        $q = $this
            ->fetchAll('select degrees.id as id, degrees.name as name, degrees.years as years, degrees.enable_sharing as enable_sharing from degrees');
        $old_degrees = []; // degree_id -> degree
        foreach($q as $record) {
            $record['id'] = intval($record['id']);
            $record['years'] = intval($record['years']);
            $record['enable_sharing'] = intval($record['enable_sharing']);
            $record['academic_years'] = [];
            $old_degrees[$record['id']] = $record;
        }
        debug($old_degrees);
        $q = $this
            ->fetchAll('select curricula.id as curricula_id, curricula.academic_year as academic_year, curricula.degree_id as degree_id from curricula');
        
        $new_degrees = []; // old_degree_id -> academic_year -> new_degree_id 

        $DegreesTable = TableRegistry::get('Degrees');

        foreach ($q as $record) {
            $record['curricula_id'] = intval($record['curricula_id']);
            $record['academic_year'] = intval($record['academic_year']);
            $record['degree_id'] = intval($record['degree_id']);
            if (array_key_exists($record['degree_id'], $new_degrees)) {
                if (array_key_exists($record['academic_year'], $new_degrees[$record['degree_id']])) {
                    // degree for given academic_year already exists!
                } else {
                    // devo clonare il vecchio degree per questo nuovo anno
                    $row = [
                        'name' => $old_degrees[$record['degree_id']]['name'],
                        'years' => $old_degrees[$record['degree_id']]['years'],
                        'enable_sharing' => $old_degrees[$record['degree_id']]['enable_sharing'],
                        'academic_year' => $record['academic_year']
                    ];
                    $table = $this->table("degrees");
                    $table->insert($row);
                    $table->saveData();
                    $result_id = $this->getAdapter()->getConnection()->lastInsertId();

                    $new_degrees[$record['degree_id']][$record['academic_year']] = $result_id;
                    array_push($old_degrees[$record['degree_id']]['academic_years'], $record['academic_year']);
                }
            } else {
                // e' il primo curriculum con questo degree, riutilizza il vecchio record:
                $new_degrees[$record['degree_id']] = [
                    $record['academic_year'] => $record['degree_id']
                ];

                array_push($old_degrees[$record['degree_id']]['academic_years'], $record['academic_year']);
            }
            debug($new_degrees);
        }

        foreach($new_degrees as $old_degree_id => $map) {
            foreach ($map as $academic_year => $new_degree_id) {
                $builder = $this->getQueryBuilder();
                $builder
                    ->update('curricula')
                    ->set('degree_id', $new_degree_id)
                    ->where([ 
                        'degree_id' => $old_degree_id,
                        'academic_year' => $academic_year
                     ])
                    ->execute();
            }
        }

        $new_groups = []; // old_group_id -> academic_year -> new_group_id

        foreach($this->fetchAll(
            'SELECT compulsory_groups.group_id as group_id, '
            . 'compulsory_groups.curriculum_id as curriculum_id, '
            . 'curricula.academic_year as academic_year, '
            . 'groups.name as name, '
            . 'curricula.degree_id as degree_id '
            . 'from compulsory_groups, curricula, groups '
            . 'where compulsory_groups.curriculum_id = curricula.id AND '
            . 'groups.id = compulsory_groups.group_id'
            ) as $row) {
            $row['group_id'] = intval($row['group_id']);
            $row['curriculum_id'] = intval($row['curriculum_id']);
            $row['academic_year'] = intval($row['academic_year']);
            $row['degree_id'] = intval($row['degree_id']);
            debug($row);
            if (array_key_exists($row['group_id'], $new_groups)) {
                if (array_key_exists($row['academic_year'], $new_groups[$row['group_id']])) {
                    // pass
                } else {
                    // duplicate group
                    $new_group_id = $this->create_group($row['name'], $row['degree_id']);
                    $new_groups[$row['group_id']][$row['academic_year']] = $new_group_id;
                }
            } else {
                // reuse group
                $new_groups[$row['group_id']] = [
                    $row['academic_year'] => $row['group_id']
                ];
            }
            debug($new_groups);
        }

        // duplicate exams_groups
        $table = $this->table("exams_groups");
        foreach($this->fetchAll('SELECT * from groups') as $group) {
            $group['id'] = intval($group['id']);
            if (array_key_exists($group['id'], $new_groups)) {
                foreach($this->fetchAll('SELECT * from exams_groups WHERE group_id = '.$group['id']) as $row) {
                    $row['exam_id'] = intval($row['exam_id']);
                    debug($row);
                    foreach($new_groups[$group['id']] as $academic_year => $new_group_id) {
                        if ($new_group_id != $group['id']) {
                            $table->insert([
                                'group_id' => $new_group_id,
                                'exam_id' => $row['exam_id']
                                ]);
                            $table->saveData();
                        }
                    }    
                }
            } else {
                print "gruppo " . $group['id'] . " inutilizzato... lo rimuovo!";
                $this->execute('DELETE FROM exams_groups WHERE group_id = ' . $group['id']);
                $this->execute('DELETE FROM exams WHERE id = ' . $group['id']);                
            }
        }
    }

    function create_group($name, $degree_id) {
        $table = $this->table("groups");
        $table->insert([
            'name' => $name,
            'degree_id' => $degree_id
        ]);
        $table->saveData();
        return $this->getAdapter()->getConnection()->lastInsertId();
    }

    public function down() 
    {
        // NOT IMPLEMENTED! SORRY!
        // we should copy the academic_year from degrees to curricula
        // and then merge together all degrees with the same name
        // removing duplicates with different years
    }
}
