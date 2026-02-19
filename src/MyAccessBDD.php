<?php

include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {

    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct() {
        try {
            parent::__construct();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */
    protected function traitementSelect(string $table, ?array $champs): ?array {
        switch ($table) {
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplaires($champs);
            case "commande" :
                return $this->selectAllCommandes($champs);
            case "abonnement" :
                return $this->selectAbonnementsRevue($champs);
            case "genre" :
            case "public" :
            case "rayon" :
            case "suivi":
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "utilisateur" :
                $res = $this->login($champs);
                return ($res ? [$res] : null);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */
    protected function traitementInsert(string $table, ?array $champs): ?int {
        switch ($table) {
            case "revue":
                return $this->insertRevue($champs);
            case "livre":
                return $this->insertLivre($champs);
            case "dvd":
                return $this->insertDvd($champs);
            case "commandedocument":
                return $this->insertCommandeDocument($champs);
            case "abonnement":
                return $this->insertAbonnement($champs);
            default:
                return $this->insertOneTupleOneTable($table, $champs);
        }
    }

    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */
    protected function traitementUpdate(string $table, ?string $id, ?array $champs): ?int {
        switch ($table) {
            case "livre" : return $this->updateLivre($id, $champs);
            case "dvd" : return $this->updateDvd($id, $champs);
            case "revue" : return $this->updateRevue($id, $champs);
            case "commandedocument" : return $this->updateSuiviCommande($id, $champs);
            case "exemplaire":
                return $this->updateEtatExemplaire($id, $champs['numero'], $champs);
            default:
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }
    }

    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */
    protected function traitementDelete(string $table, ?array $champs): ?int {
        switch ($table) {
            case "livre": return $this->deleteLivre($champs);
            case "dvd": return $this->deleteDvd($champs);
            case "revue": return $this->deleteRevue($champs);
            case "commande":
                return $this->deleteCommande($champs);
            case "exemplaire":
                return $this->deleteExemplaire($champs);
            default:
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);
        }
    }

    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs): ?array {
        if (empty($champs)) {
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);
        } else {
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value) {
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete) - 5);
            return $this->conn->queryBDD($requete, $champs);
        }
    }

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */
    private function insertOneTupleOneTable(string $table, ?array $champs): ?int {
        if (empty($champs)) {
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value) {
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $requete .= ") values (";
        foreach ($champs as $key => $value) {
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs): ?int {
        if (empty($champs)) {
            return null;
        }
        if (is_null($id)) {
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value) {
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $champs["id"] = $id;
        $requete .= " where id=:id;";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs): ?int {
        if (empty($champs)) {
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value) {
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete) - 5);
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table): ?array {
        $requete = "select * from $table order by libelle;";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres(): ?array {
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd(): ?array {
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues(): ?array {
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère tous les exemplaires d'un document
     * @param array|null $champs 
     * @return array|null
     */
    private function selectExemplaires(?array $champs): ?array {
        if (empty($champs) || !array_key_exists('id', $champs)) {
            return null;
        }

        $champNecessaire['id'] = $champs['id'];

        $requete = "SELECT e.id, e.numero, e.dateAchat, e.photo, e.idEtat, et.libelle AS libelleEtat ";
        $requete .= "FROM exemplaire e ";
        $requete .= "JOIN etat et ON e.idEtat = et.id ";
        $requete .= "WHERE e.id = :id ";
        $requete .= "ORDER BY e.dateAchat DESC";

        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * récupère toutes les commandes ou les commandes d'un document spécifique
     * @param array|null $champs
     * @return array|null
     */
    private function selectAllCommandes(?array $champs): ?array {
        if (empty($champs) || !isset($champs['id'])) {
            $requete = "SELECT id, dateCommande, montant FROM commande ORDER BY dateCommande DESC;";
            return $this->conn->queryBDD($requete);
        } else {
            $requete = "SELECT c.id, cd.idLivreDvd, c.dateCommande, c.montant, cd.nbExemplaire, cd.idsuivi, s.libelle as libelleSuivi ";
            $requete .= "FROM commande c ";
            $requete .= "JOIN commandedocument cd ON c.id = cd.id ";
            $requete .= "JOIN suivi s ON cd.idsuivi = s.id ";
            $requete .= "WHERE cd.idLivreDvd = :id ";
            $requete .= "ORDER BY c.dateCommande DESC;";
            return $this->conn->queryBDD($requete, ['id' => $champs['id']]);
        }
    }

    /**
     * récupère les abonnements d'une revue ou tous les abonnements
     * @param array|null $champs
     * @return array|null
     */
    private function selectAbonnementsRevue(?array $champs): ?array {
        $requete = "SELECT c.id, c.dateCommande, c.montant, a.dateFinAbonnement, a.idRevue ";
        $requete .= "FROM commande c ";
        $requete .= "JOIN abonnement a ON c.id = a.id ";

        if (empty($champs) || !isset($champs['id'])) {
            $requete .= " ORDER BY c.dateCommande DESC";
            return $this->conn->queryBDD($requete);
        } else {
            $requete .= " WHERE a.idRevue = :idR ";
            $requete .= " ORDER BY c.dateCommande DESC";
            return $this->conn->queryBDD($requete, ['idR' => $champs['id']]);
        }
    }

    /**
     * tente une connexion utilisateur et retourne ses informations
     * @param array|null $champs email et password
     * @return array|null informations utilisateur ou null
     */
    private function login(?array $champs): ?array {
        $email = $champs['email'] ?? null;
        $pwd = $champs['password'] ?? null;

        if ($email === null || $pwd === null)
            return null;

        $requete = "SELECT u.id, u.email, u.password, u.idservice, s.libelle 
                FROM utilisateur u 
                JOIN service s ON u.idservice = s.id 
                WHERE u.email = :email";

        $user = $this->conn->queryBDD($requete, ['email' => $email]);

        if ($user && count($user) > 0) {
            if (password_verify($pwd, $user[0]['password'])) {
                unset($user[0]['password']);
                return $user[0];
            }
        }
        return null;
    }

    /**
     * Ajoute une revue en gérant la transaction sur les deux tables
     * @param array $champs
     * @return int|null
     */
    private function insertRevue(array $champs): ?int {
        try {
            $this->conn->beginTransaction();

            $champsDoc = [
                'id' => $champs['id'],
                'titre' => $champs['titre'],
                'image' => $champs['image'],
                'idRayon' => $champs['idRayon'],
                'idPublic' => $champs['idPublic'],
                'idGenre' => $champs['idGenre']
            ];
            $resDoc = $this->insertOneTupleOneTable('document', $champsDoc);

            $champsRevue = [
                'id' => $champs['id'],
                'periodicite' => $champs['periodicite'],
                'delaiMiseADispo' => $champs['delaiMiseADispo']
            ];
            $resRevue = $this->insertOneTupleOneTable('revue', $champsRevue);

            if ($resDoc !== null && $resRevue !== null) {
                $this->conn->commit();
                return 1;
            } else {
                $this->conn->rollback();
                return null;
            }
        } catch (\Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Ajoute un livre en gérant la transaction sur les tables document, livres_dvd et livre
     * @param array $champs
     * @return int|null
     */
    private function insertLivre(array $champs): ?int {
        try {
            $this->conn->beginTransaction();

            $resDoc = $this->insertOneTupleOneTable('document', [
                'id' => $champs['id'],
                'titre' => $champs['titre'],
                'image' => $champs['image'],
                'idRayon' => $champs['idRayon'],
                'idPublic' => $champs['idPublic'],
                'idGenre' => $champs['idGenre']
            ]);

            $resLD = $this->insertOneTupleOneTable('livres_dvd', [
                'id' => $champs['id']
            ]);

            $resLivre = $this->insertOneTupleOneTable('livre', [
                'id' => $champs['id'],
                'ISBN' => $champs['ISBN'],
                'auteur' => $champs['auteur'],
                'collection' => $champs['collection']
            ]);

            if ($resDoc !== null && $resLD !== null && $resLivre !== null) {
                $this->conn->commit();
                return 1;
            } else {
                $this->conn->rollback();
                return null;
            }
        } catch (\Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * Ajoute un DVD en gérant la transaction sur les tables document, livres_dvd et dvd
     * @param array $champs
     * @return int|null
     */
    private function insertDvd(array $champs): ?int {
        try {
            $this->conn->beginTransaction();

            $resDoc = $this->insertOneTupleOneTable('document', [
                'id' => $champs['id'],
                'titre' => $champs['titre'],
                'image' => $champs['image'],
                'idRayon' => $champs['idRayon'],
                'idPublic' => $champs['idPublic'],
                'idGenre' => $champs['idGenre']
            ]);

            $resLD = $this->insertOneTupleOneTable('livres_dvd', [
                'id' => $champs['id']
            ]);

            $resDvd = $this->insertOneTupleOneTable('dvd', [
                'id' => $champs['id'],
                'synopsis' => $champs['synopsis'],
                'realisateur' => $champs['realisateur'],
                'duree' => $champs['duree']
            ]);

            if ($resDoc !== null && $resLD !== null && $resDvd !== null) {
                $this->conn->commit();
                return 1;
            } else {
                $this->conn->rollback();
                return null;
            }
        } catch (\Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Ajoute une commande de document avec gestion de transaction sur les tables commandeDocument et commande
     * @param array $champs
     * @return int|null
     */
    private function insertCommandeDocument(array $champs): ?int {
        try {
            $this->conn->beginTransaction();

            $this->insertOneTupleOneTable('commande', [
                'id' => $champs['id'],
                'dateCommande' => $champs['dateCommande'],
                'montant' => $champs['montant']
            ]);

            $res = $this->insertOneTupleOneTable('commandedocument', [
                'id' => $champs['id'],
                'nbExemplaire' => $champs['nbExemplaire'],
                'idLivreDvd' => $champs['idLivreDvd'],
                'idsuivi' => '00001'
            ]);

            $this->conn->commit();
            return $res;
        } catch (\Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Ajoute un abonnement avec gestion de transaction sur les tables abonnement et commande
     * @param array $champs
     * @return int|null
     */
    private function insertAbonnement(array $champs): ?int {
        try {
            $this->conn->beginTransaction();
            $this->insertOneTupleOneTable('commande', [
                'id' => $champs['id'],
                'dateCommande' => $champs['dateCommande'],
                'montant' => $champs['montant']
            ]);
            $res = $this->insertOneTupleOneTable('abonnement', [
                'id' => $champs['id'],
                'dateFinAbonnement' => $champs['dateFinAbonnement'],
                'idRevue' => $champs['idRevue']
            ]);
            $this->conn->commit();
            return $res;
        } catch (\Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Supprime une commande
     * @param array|null $champs contient l'id de la commande
     * @return int|null nombre de lignes supprimées
     */
    private function deleteCommande(?array $champs): ?int {
        $id = (is_array($champs) && isset($champs['id'])) ? $champs['id'] : null;

        if (is_null($id)) {
            return null;
        }

        try {
            $this->conn->beginTransaction();
            $res = $this->conn->updateBDD("delete from commande where id=:id", ['id' => $id]);

            $this->conn->commit();
            return $res;
        } catch (\Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Supprime un livre et ses dépendances (livre, livres_dvd, document)
     * @param array|null $champs contient l'id du livre
     * @return int|null nombre de lignes supprimées (table document)
     */
    private function deleteLivre(?array $champs): ?int {
        $id = (is_array($champs) && isset($champs['id'])) ? $champs['id'] : null;

        if (is_null($id)) {
            return null;
        }
        try {
            $this->conn->beginTransaction();

            $this->conn->updateBDD("delete from livre where id=:id", ['id' => $id]);
            $this->conn->updateBDD("delete from livres_dvd where id=:id", ['id' => $id]);
            $resDoc = $this->conn->updateBDD("delete from document where id=:id", ['id' => $id]);

            $this->conn->commit();
            return $resDoc;
        } catch (\Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Supprime un DVD et ses dépendances (dvd, livres_dvd, document)
     * @param array|null $champs contient l'id du DVD
     * @return int|null nombre de lignes supprimées (table document)
     */
    private function deleteDvd(?array $champs): ?int {
        $id = (is_array($champs) && isset($champs['id'])) ? $champs['id'] : null;

        if (is_null($id)) {
            return null;
        }

        try {
            $this->conn->beginTransaction();
            $this->conn->updateBDD("delete from dvd where id=:id", ['id' => $id]);
            $this->conn->updateBDD("delete from livres_dvd where id=:id", ['id' => $id]);
            $resDoc = $this->conn->updateBDD("delete from document where id=:id", ['id' => $id]);

            $this->conn->commit();
            return $resDoc;
        } catch (\Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Supprime une revue et ses dépendances (revue, document)
     * @param array|null $champs contient l'id de la revue
     * @return int|null nombre de lignes supprimées (table document)
     */
    private function deleteRevue(?array $champs): ?int {
        $id = (is_array($champs) && isset($champs['id'])) ? $champs['id'] : null;

        if (is_null($id)) {
            return null;
        }
        try {
            $this->conn->beginTransaction();
            $this->conn->updateBDD("delete from revue where id=:id", ['id' => $id]);
            $resDoc = $this->conn->updateBDD("delete from document where id=:id", ['id' => $id]);

            $this->conn->commit();
            return $resDoc;
        } catch (\Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Supprime un exemplaire spécifique d'un document
     * @param array|null $champs contient l'id et le numéro d'exemplaire
     * @return int|null
     */
    private function deleteExemplaire(?array $champs): ?int {
        $id = (is_array($champs) && isset($champs['id'])) ? $champs['id'] : null;
        $numero = (is_array($champs) && isset($champs['numero'])) ? $champs['numero'] : null;
        if (is_null($id)) {
            return null;
        }
        try {
            $this->conn->beginTransaction();
            $res = $this->conn->updateBDD("delete from exemplaire where id=:id AND numero=:numero", ['id' => $id, 'numero' => $numero]);
            $this->conn->commit();
            return $res;
        } catch (\Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Met à jour un livre sur les tables document et livre via une transaction
     * @param string $id
     * @param array $champs
     * @return int|null
     */
    private function updateLivre(string $id, array $champs): ?int {
        try {
            $this->conn->beginTransaction();

            $resDoc = $this->updateOneTupleOneTable('document', $id, [
                'titre' => $champs['titre'],
                'image' => $champs['image'],
                'idRayon' => $champs['idRayon'],
                'idPublic' => $champs['idPublic'],
                'idGenre' => $champs['idGenre']
            ]);

            $resLivre = $this->updateOneTupleOneTable('livre', $id, [
                'ISBN' => $champs['ISBN'],
                'auteur' => $champs['auteur'],
                'collection' => $champs['collection']
            ]);

            if ($resDoc !== null && $resLivre !== null) {
                $this->conn->commit();
                return 1;
            } else {
                $this->conn->rollback();
                return null;
            }
        } catch (\Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Met à jour un DVD sur les tables document et dvd via une transaction
     * @param string $id
     * @param array $champs
     * @return int|null
     */
    private function updateDvd(string $id, array $champs): ?int {
        try {
            $this->conn->beginTransaction();
            $resDoc = $this->updateOneTupleOneTable('document', $id, [
                'titre' => $champs['titre'],
                'image' => $champs['image'],
                'idRayon' => $champs['idRayon'],
                'idPublic' => $champs['idPublic'],
                'idGenre' => $champs['idGenre']
            ]);

            $resDvd = $this->updateOneTupleOneTable('dvd', $id, [
                'synopsis' => $champs['synopsis'], 'realisateur' => $champs['realisateur'], 'duree' => $champs['duree']
            ]);
            if ($resDoc !== null && $resDvd !== null) {
                $this->conn->commit();
                return 1;
            } else {
                $this->conn->rollback();
                return null;
            }
        } catch (\Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Met à jour une revue sur les tables document et revue via une transaction
     * @param string $id
     * @param array $champs
     * @return int|null
     */
    private function updateRevue(string $id, array $champs): ?int {
        try {
            $this->conn->beginTransaction();
            $resDoc = $this->updateOneTupleOneTable('document', $id, [
                'titre' => $champs['titre'], 'image' => $champs['image'],
                'idRayon' => $champs['idRayon'], 'idPublic' => $champs['idPublic'], 'idGenre' => $champs['idGenre']
            ]);
            $resRevue = $this->updateOneTupleOneTable('revue', $id, [
                'periodicite' => $champs['periodicite'],
                'delaiMiseADispo' => $champs['delaiMiseADispo']
            ]);

            if ($resDoc !== null && $resRevue !== null) {
                $this->conn->commit();
                return 1;
            } else {
                $this->conn->rollback();
                return null;
            }
        } catch (\Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Met à jour l'état de suivi d'une commande
     * @param string $id id de la commande
     * @param array $champs contient l'idsuivi
     * @return int|null
     */
    private function updateSuiviCommande(string $id, array $champs): ?int {
        if (!isset($champs['idsuivi'])) {
            return null;
        }
        $requete = "update commandedocument set idsuivi = :idsuivi where id = :id;";
        $params = [
            'id' => $id,
            'idsuivi' => $champs['idsuivi']
        ];
        return $this->conn->updateBDD($requete, $params);
    }
    
    /**
     * Met à jour l'état d'un exemplaire spécifique
     * @param string $id id du document
     * @param string $numero numéro de l'exemplaire
     * @param array $champs contient l'idetat
     * @return int|null
     */
    private function updateEtatExemplaire(string $id, string $numero, array $champs): ?int {
        if (!isset($champs['idetat'])) {
            return null;
        }
        $requete = "update exemplaire set idetat = :idetat where id = :id AND numero = :numero;";
        $params = [
            'id' => $id,
            'numero' => $numero,
            'idetat' => $champs['idetat']
        ];
        return $this->conn->updateBDD($requete, $params);
    }
}
