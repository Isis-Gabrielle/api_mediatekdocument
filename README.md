<h1>Présentation de l'API</h1>
Cette API, écrite en PHP, est basée sur l'existant présentée dans le dépôt suivant qui contient, dans le readme, la présentation de l'API d'origine:<br>
https://github.com/CNED-SLAM/rest_mediatekdocuments<br>
Le readme de ce dépôt présente la structure de la base de l'API (rôle de chaque fichier) et comment l'exploiter.<br>
Les ajouts faits dans cette API ne concernent que les fichiers '.env' (qui contient les données sensibles d'authentification et d'accès à la BDD) et 'MyAccessBDD.php' (dans lequel de nouvelles fonctions ont été ajoutées pour répondre aux demandes de l'application).<br>
Cette API permet d'exécuter des requêtes SQL sur la BDD Mediatek86 créée avec le SGBDR MySQL.<br>
Elle est accessible via une authentification "basique" (login="admin", pwd="adminpwd").<br>
Sa vocation actuelle est de répondre aux demandes de l'application MediaTekDocuments, mise en ligne sur le dépôt :<br>
https://github.com/Isis-Gabrielle/mediatekdocument

<h1>Installation de l'API en local</h1>
Pour tester l'API REST en local, voici le mode opératoire :
<ul>
   <li>Installer les outils nécessaires (WampServer ou équivalent, NetBeans ou équivalent pour gérer l'API dans un IDE, Postman pour les tests).</li>
   <li>Télécharger le zip du code de l'API et le dézipper dans le dossier www de wampserver.</li>
   <li>Si 'Composer' n'est pas installé, le télécharger avec ce lien et l'insstaller : https://getcomposer.org/Composer-Setup.exe </li>
   <li>Dans une fenêtre de commandes ouverte en mode admin, aller dans le dossier de l'API et taper 'composer install' puis valider pour recréer le vendor.</li>
   <li>Récupérer le script metiak86.sql en racine du projet puis, avec phpMyAdmin, créer la BDD mediatek86 et, dans cette BDD, exécuter le script pour remplir la BDD.</li>
</ul>
<h1>Exploitation de l'API</h1>
Adresse de l'API (en local) : http://localhost/api_mediatekdocuments/ <br>
Voici les différentes possibilités de sollicitation de l'API, afin d'agir sur la BDD, en ajoutant des informations directement dans l'URL (visible) et éventuellement dans le body (invisible) suivant les besoins : 
<h2>Récupérer un contenu (select)</h2>
Méthode HTTP : <strong>GET</strong><br>
http://localhost/api_mediatekdocuments/table/champs (champs optionnel)
<ul>
   <li>'table' doit être remplacé par un nom de table (caractères acceptés : alphanumériques et '_')</li>
   <li>'champs' (optionnel) doit être remplacé par la liste des champs (nom/valeur) qui serviront à la recherche (au format json)</li>
</ul>

<h2>Insérer (insert)</h2>
Méthode HTTP : <strong>POST</strong><br>
http://localhost/api_mediatekdocuments/table <br>
'table' doit être remplacé par un nom de table (caractères acceptés : alphanumériques et '_')<br>
Dans le body (Dans Postman, onglet 'Body', cocher 'x-www-form-urlencoded'), ajouter :<br>
<ul>
   <li>Key : 'champs'</li>
   <li>Value : liste des champs (nom/valeur) qui serviront à l'insertion (au format json)</li>
</ul>

<h2>Modifier (update)</h2>
Méthode HTTP : <strong>PUT</strong><br>
http://localhost/api_mediatekdocuments/table/id (id optionnel)<br>
<ul>
   <li>'table' doit être remplacé par un nom de table (caractères acceptés : alphanumériques et '_')</li>
   <li>'id' (optionnel) doit être remplacé par l'identifiant de la ligne à modifier (caractères acceptés : alphanumériques)</li>
</ul>
Dans le body (Dans Postman, onglet 'Body', cocher 'x-www-form-urlencoded'), ajouter :<br>
<ul>
   <li>Key : 'champs'</li>
   <li>Value : liste des champs (nom/valeur) qui serviront à la modification (au format json)</li>
</ul>

<h2>Supprimer (delete)</h2>
Méthode HTTP : <strong>DELETE</strong><br>
http://localhost/api_mediatekdocuments/table/champs (champs optionnel)<br>
<ul>
   <li>'table' doit être remplacé par un nom de table (caractères acceptés : alphanumériques et '_')</li>
   <li> 'champs' (optionnel) doit être remplacé par la liste des champs (nom/valeur) qui serviront déterminer les lignes à supprimer (au format json</li>
</ul>

<h1>Les fonctionnalités ajoutées</h1>
Dans MyAccessBDD, plusieurs fonctions ont été ajoutées pour répondre aux demandes actuelles de l'application C# MediaTekDocuments :<br>
<h2>GET</h2>
<ul>
   <li><strong>selectAllCommandes : </strong>récupère toutes les commandes ou les commandes d'un document spécifique</li>
   <li><strong>selectAbonnementsRevue : </strong>récupère les abonnements d'une revue ou tous les abonnements</li>
   <li><strong>login : </strong>tente une connexion utilisateur et retourne ses informations</li>
</ul>
<h2>POST</h2>
<ul>
   <li><strong>insertRevue : </strong>Ajoute une revue en gérant la transaction sur les deux tables</li>
   <li><strong>insertLivre : </strong>Ajoute un livre en gérant la transaction sur les tables document, livres_dvd et livre</li>
   <li><strong>insertDvd : </strong>Ajoute un DVD en gérant la transaction sur les tables document, livres_dvd et dvd</li>
   <li><strong>insertCommandeDocument : </strong>Ajoute une commande de document avec gestion de transaction sur les tables commandeDocument et commande</li>
   <li><strong>insertAbonnement : </strong>Ajoute un abonnement avec gestion de transaction sur les tables abonnement et commande</li>
</ul>
<h2>DELETE</h2>
<ul>
   <li><strong>deleteCommande : </strong>Supprime une commande</li>
   <li><strong>deleteLivre : </strong>Supprime un livre et ses dépendances (livre, livres_dvd, document)</li>
   <li><strong>deleteDvd : </strong>Supprime un DVD et ses dépendances (dvd, livres_dvd, document)</li>
   <li><strong>deleteRevue : </strong>Supprime une revue et ses dépendances (revue, document)</li>
   <li><strong>deleteExemplaire : </strong>Supprime un exemplaire spécifique d'un document</li>
</ul>
<h2>PUT</h2>
<ul>
   <li><strong>updateLivre : </strong>Met à jour un livre sur les tables document et livre via une transaction</li>
   <li><strong>updateDvd : </strong>Met à jour un DVD sur les tables document et dvd via une transaction</li>
   <li><strong>updateRevue : </strong>Met à jour une revue sur les tables document et revue via une transaction</li>
   <li><strong>updateSuiviCommande : </strong>Met à jour l'état de suivi d'une commande</li>
   <li><strong>updateEtatExemplaire : </strong>Met à jour l'état d'un exemplaire spécifique</li>
</ul>
