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
declare(strict_types=1);

use Migrations\AbstractMigration;

class Utf8Tables extends AbstractMigration
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
        $conn = $this->getAdapter()->getConnection();
        $tables = [ 'attachments', 'chosen_exams', 'chosen_free_choice_exams', 
            'compulsory_exams', 'compulsory_groups', 'curricula',
            'degrees', 'documents', 'exams', 'exams_groups', 'free_choice_exams', 
            'groups', 'proposal_auths', 'proposals', 'settings', 'tags', 'tags_exams', 
            'users'  ];

        // If the database is MySQL, we convert all table to use the utf8mb4 encoding. 
        if ($conn->getAttribute(PDO::ATTR_DRIVER_NAME) == "mysql")
        {
            foreach ($tables as $table) 
            {
                echo "Converting table $table to utf8mb4 ... ";
                $conn->query("ALTER table `$table` CONVERT TO CHARACTER SET utf8mb4");
                echo "done\n";
            }
        }
    }

    public function down() {

    }
}