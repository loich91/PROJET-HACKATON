<?php
class MyPDO extends PDO
{
    /*
    Paramètres pour la mise en ligne sur ovh
    private $driver   = 'mysql';
    private $host     = 'localhost:8889';
    private $db       = 'univerbal';
    private $user     = 'root';
    private $password = 'root';
    private $charset  = 'utf8mb4';
    private $online   = false;
    private $port     = '35333';
    */
    private $driver   = 'mysql';
    private $host     = 'localhost:3306';
    private $db       = 'univerbal2';
    private $user     = 'root';
    private $password = '';
    private $charset  = 'utf8mb4';
    private $online   = false;
    //private $port     = '';

    // private $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    private $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    public function __construct()
    {
        $bdd = null;

        $dsn = "$this->driver:host=$this->host;dbname=$this->db;charset=$this->charset";

        try {
            // $bdd = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $dbuser, $dbpwd);
            $bdd = parent::__construct($dsn, $this->user, $this->password, $this->options);
            return $bdd;
        } catch (Exception $e) {
            if ($this->online === false) {
                die('Erreur : ' . $e->getMessage());
            } else {
                die('Erreur avec la BD contacter le support technique...');
            }
        }
    }
}
