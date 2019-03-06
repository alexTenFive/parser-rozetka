<?php
namespace App\db\Tools;

/**
 * @class DBQuery
 * Need for simple way to act with DB
 */
class DBQuery
{    
    /**
     * @param string $table
     * @param array $data
     * 
     *  Insert data into specific $table
     * 
     * @return void
     */
    public static function insert(string $table, array $data): int
    {
        $db = DB::getConnection();
        
        try {
            if (! isset($data)) throw new \Exception('Data for insert not specified');
            
            $countFields = count($data);
            $fieldsString = '';
            $valuesString = '';

            foreach (range(1, $countFields) as $c)
            {
                $fieldsString .= ($c == $countFields) ? '%s' : '%s, ';
                $valuesString .= ($c == $countFields) ? '?' : '?, ';

            }

            $sql = sprintf("INSERT IGNORE INTO %s ($fieldsString) VALUES ($valuesString)", $table, ...array_keys($data));
            $smtp = $db->prepare($sql);
            $smtp->execute(array_values($data));

            return $db->lastInsertId();
        } catch (\Exception $e) {
            http_response_code(500);
            echo sprintf("<b style='font-size: 22px'>Error!</b><br><b>File: %s</b><br><b>Line: %d</b><br><b>Message:</b> %s<hr>", $e->getFile(), $e->getLine(), $e->getMessage());
            exit();
        }
    }

    /**
     * @param string $table Table name
     * @param array $where = [] where clause operations
     * @param array $fields = [] fields for select. If not specified - '*'
     * 
     * @return array
     */
    public static function select(string $table, array $where = [], array $fields = []): array
    {
        $db = DB::getConnection();

        $countFields = count($fields);
        try {
            $fieldsString = '';
            $whereClause = '';
            $whereValues = [];

            if ((bool) $countFields) {
                foreach (range(1, $countFields) as $c) {
                    $fieldsString .= ($c == $countFields) ? '%s' : '%s, ';
                }
            } else {
                $fieldsString = '*';
            }

            if ((bool) count($where)) {
                /**
                 * $operation = ['field', 'operation', 'value']
                 */
                $whereClause = "WHERE ";
                foreach ($where as $k => $operation) {
                    if (is_array($operation) && count($operation) == 3) {
                        $whereClause .= sprintf("(%s %s ?)", $operation[0], $operation[1]);
                        
                        $whereValues[] = $operation[2];

                        if (count($where) > 1 && $k != count($where) - 1) {
                            $whereClause .= " AND ";
                        }
                    } else {
                        throw new \Exception("Where clause params must be of array type and contain structure like this: ['field', 'operation', 'value']");
                    }
                }
            }

            $sql = sprintf("SELECT $fieldsString ", ...array_values($fields));
            $sql .= sprintf("FROM %s %s", $table, $whereClause); 
            
            $stmt = $db->prepare($sql);
            $stmt->execute($whereValues);
            
            $data = $stmt->fetchAll();

            if (count($data)) {
                return $data;
            }

            return [];
        } catch (\Exception $e) {
            http_response_code(500);
            echo sprintf("<b style='font-size: 22px'>Error!</b><br><b>File: %s</b><br><b>Line: %d</b><br><b>Message:</b> %s<hr>", $e->getFile(), $e->getLine(), $e->getMessage());
            exit();
        }
    }
}