<?php

return [
    'Maintenance module configuration' => 'Configuration du module Maintenance',
    'Disable maintenance' => 'Désactiver la maintenance',
    'Enable maintenance' => 'Activer la maintenance',
    'Title' => 'Titre',
    'Message' => 'Message',
    'Background color' => 'Couleur de fond',
    'Font color' => 'Couleur du texte',
    'Link color' => 'Couleur des liens',
    'Save for preview' => 'Enregistrer pour prévisualiser',
    'The shop is closed: visitors get the maintenance page (HTTP 503). The back office, signed-in administrators and the allowed paths and addresses below still get through.' => 'La boutique est fermée : les visiteurs reçoivent la page de maintenance (HTTP 503). Le back-office, les administrateurs connectés et les chemins et adresses autorisés ci-dessous passent toujours.',
    'A maintenance guard written by a previous version of this module is still in public/index.php: it keeps the shop closed whatever this switch says. Remove the block that starts with //⚠--MAINTENANCE by hand.' => 'Un blocage de maintenance écrit par une version précédente de ce module est encore dans public/index.php : il garde la boutique fermée quel que soit cet interrupteur. Supprimez à la main le bloc qui commence par //⚠--MAINTENANCE.',
    'The public directory is not writable: the maintenance page cannot be saved.' => 'Le répertoire public n\'est pas accessible en écriture : la page de maintenance ne peut pas être enregistrée.',
    'Access during maintenance' => 'Accès pendant la maintenance',
    'Allowed paths' => 'Chemins autorisés',
    'One path per line. A path also lets its sub-paths through: /module/paypal covers /module/paypal/notify. Use it for the return URLs of payment providers.' => 'Un chemin par ligne. Un chemin laisse aussi passer ses sous-chemins : /module/paypal couvre /module/paypal/notify. À utiliser pour les URL de retour des prestataires de paiement.',
    'Allowed IP addresses' => 'Adresses IP autorisées',
    'One address or CIDR range per line.' => 'Une adresse ou une plage CIDR par ligne.',
    'Your address: %ip' => 'Votre adresse : %ip',
    'Save' => 'Enregistrer',
];
