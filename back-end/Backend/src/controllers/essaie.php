
<?php
// 3. POST sans ID = Ajout d'une nouvelle entrée
        if ($request->getMethod() == 'POST' && !isset($args['id'])) {
            // je vérifie que les données que je souhaite sont bien présente

            if(
                isset($_REQUEST['firstname']) && $_REQUEST['firstname'] != ''
            && isset($_REQUEST['lastname']) && $_REQUEST['lastname'] != '' &&
            isset($_REQUEST['sex']) && $_REQUEST['sex'] != ''
            && isset($_REQUEST['email']) && $_REQUEST['email'] != ''
            && isset($_REQUEST['roles_id']) && $_REQUEST['roles_id'] != ''
            ){
                 //Est-ce que l'email est valide ?
            if (filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) {

                if ($this->isEmailExist($_REQUEST['email'])) {
                    $data['status'] = 'error';
                    $data['code'] = 'emailexist';
                    $data['message'] = "Désolé, l'email existe ";
                } else {

                     // je crée ma requête et la prépare
                $sql = "INSERT INTO users SET firstname = :firstname,
                lastname = :lastname,
                sex = :sex,
                roles_id = :roles_id,
                email = :email,
                password = :password;";
            $stmnt = $db->prepare($sql);

            $password = $this->password_random(10);

            //on hash le mot de passe pour le protéger
            $password_hashed = password_hash($password,  PASSWORD_DEFAULT);

            // je passe à ma requête les différentes paramètres requis
            $stmnt->bindValue(":firstname", $_REQUEST['firstname'], PDO::PARAM_STR);
            $stmnt->bindValue(":lastname", $_REQUEST['lastname'], PDO::PARAM_STR);
            $stmnt->bindValue(":sex", $_REQUEST['sex'], PDO::PARAM_STR);
            $stmnt->bindValue(":roles_id", $_REQUEST['roles_id'], PDO::PARAM_STR);
            $stmnt->bindValue(":email", $_REQUEST['email'], PDO::PARAM_STR);
            $stmnt->bindValue(":password", $password_hashed, PDO::PARAM_STR);

            // Exécution de la requête
            $stmnt->execute();
            if ($stmnt && $stmnt->rowCount() > 0) {
                $firstname = $_REQUEST['firstname'];
                $lastname = $_REQUEST['lastname'];
                $email = $_REQUEST['email'];
                $roles = $_REQUEST['roles_id'];

                // Use your saved credentials, specify that you are using Send API v3.1

                $mj = new \Mailjet\Client('37ba9cb6bffd1f15aac476a702633bc0', 'e7af3652883f7073c28a999a345d0f8a', true, ['version' => 'v3.1']);
                // require_once __DIR__ . '/../vendor/autoload.php';

                // Configure API key authorization: api-key
                $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', '');

                // Define your request body

                $body = [
                    'Messages' => [
                        [
                            'From' => [
                                'Email' => "mohkeita@proximus.be",
                                'Name' => "Univerbal"
                            ],
                            'To' => [
                                [
                                    'Email' => "$email",
                                    'Name' => "$firstname $lastname"
                                ]
                            ],
                            'TemplateID' => 1075001,
                            'TemplateLanguage' => true,
                            'Subject' => "Inscription Univerbal",
                            'Variables' => json_decode('{
                            "firstname": ' . json_encode($firstname) . ',
                            "lastname": ' . json_encode($lastname) . ',
                            "mail": ' . json_encode($email) . ',
                            "password": ' . json_encode($password) . ',
                            "roles_id": ' . json_encode($roles) . '
                            }', true)

                        ]
                    ]
                ];

                // All resources are located in the Resources class

                $responses = $mj->post(Resources::$Email, ['body' => $body]);

                // Read the response

                $responses->success() && var_dump($responses->getData());

            
                $httpCode = 200;
                $data['status'] = 'success';

            }else {
                $data['status'] = 'error';
                $data['code'] = 'sqlProblem';
                $data['content'] = 'Désolé impossible d\'exécuter la requête...';
            }

            }

            } else {
                $data['status'] = 'error';
                $data['code'] = 'invalideEmail';
                $data['message'] = 'Désolé, email invalide';
            }


        } else {
                $data['status'] = 'error';
                $data['code'] = 'paramMissing';
                $data['message'] = 'Désolé, tous les paramètres sont obligatoire: email, roles_id, sex, lastname, firstname';
            }
            
        }




            
        

       







        

    