<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component aiprovider_bedrock, language 'it'.
 *
 * @package    aiprovider_bedrock
 * @copyright  2025 Davide Ferro <dferro@meeplesrl.it>, Angelo Calò <acalo@meeplesrl.it>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['accesskeyid'] = 'ID chiave di accesso AWS';
$string['accesskeyid_desc'] = 'L\'ID chiave di accesso AWS utilizzato per autenticarsi con Amazon Bedrock. Non richiesto se "Usa la catena predefinita di provider di credenziali AWS" è abilitato sopra.';
$string['action:generate_image:model'] = 'Modello IA';
$string['action:generate_image:model_desc'] = 'Il modello utilizzato per generare immagini dai prompt degli utenti.';
$string['action:generate_text:model'] = 'Modello IA';
$string['action:generate_text:model_desc'] = 'Il modello utilizzato per generare la risposta testuale. I modelli cross-region inference richiedono "us." o "eu." all\'inizio.';
$string['action:generate_text:systeminstruction'] = 'Istruzione di sistema';
$string['action:generate_text:systeminstruction_desc'] = 'Questa istruzione viene inviata al modello IA insieme al prompt dell\'utente. Si sconsiglia di modificare questa istruzione a meno che non sia assolutamente necessario.';
$string['action:summarise_text:model'] = 'Modello IA';
$string['action:summarise_text:model_desc'] = 'Il modello utilizzato per riassumere il testo fornito.';
$string['action:summarise_text:systeminstruction'] = 'Istruzione di sistema';
$string['action:summarise_text:systeminstruction_desc'] = 'Questa istruzione viene inviata al modello IA insieme al prompt dell\'utente. Si sconsiglia di modificare questa istruzione a meno che non sia assolutamente necessario.';
$string['enableglobalratelimit'] = 'Imposta limite di velocità a livello di sito';
$string['enableglobalratelimit_desc'] = 'Limita il numero di richieste che il provider API Amazon Bedrock può ricevere in tutto il sito ogni ora.';
$string['enableuserratelimit'] = 'Imposta limite di velocità utente';
$string['enableuserratelimit_desc'] = 'Limita il numero di richieste che ogni utente può fare al provider API Amazon Bedrock ogni ora.';
$string['error:failedprocessimage'] = 'Impossibile elaborare l\'immagine';
$string['error:globalratelimitexceeded'] = 'Limite di velocità globale superato';
$string['error:noimagedata'] = 'Nessuna informazione sull\'immagine trovata nella risposta';
$string['error:unknownerror'] = 'Errore sconosciuto';
$string['error:userratelimitexceeded'] = 'Limite di velocità utente superato';
$string['globalratelimit'] = 'Numero massimo di richieste a livello di sito';
$string['globalratelimit_desc'] = 'Il numero di richieste a livello di sito consentite all\'ora.';
$string['pluginname'] = 'Provider API Amazon Bedrock';
$string['privacy:metadata'] = 'Il plugin provider API Amazon Bedrock non memorizza alcun dato personale.';
$string['privacy:metadata:aiprovider_bedrock:externalpurpose'] = 'Queste informazioni vengono inviate all\'API Amazon Bedrock per generare una risposta. Le impostazioni del tuo account AWS possono modificare il modo in cui Amazon memorizza e conserva questi dati. Nessun dato utente viene esplicitamente inviato ad Amazon o memorizzato in Moodle LMS da questo plugin.';
$string['privacy:metadata:aiprovider_bedrock:model'] = 'Il modello utilizzato per generare la risposta.';
$string['privacy:metadata:aiprovider_bedrock:numberimages'] = 'Durante la generazione delle immagini, il numero di immagini utilizzate nella risposta.';
$string['privacy:metadata:aiprovider_bedrock:prompttext'] = 'Il prompt di testo inserito dall\'utente utilizzato per generare la risposta.';
$string['privacy:metadata:aiprovider_bedrock:responseformat'] = 'Durante la generazione delle immagini, il formato della risposta.';
$string['region'] = 'Regione AWS';
$string['region_desc'] = 'La regione AWS dove il servizio Amazon Bedrock è disponibile (es., eu-west-1, us-east-1, us-west-2).';
$string['secretaccesskey'] = 'Chiave di accesso segreta AWS';
$string['secretaccesskey_desc'] = 'La chiave di accesso segreta AWS utilizzata per autenticarsi con Amazon Bedrock. Non richiesta se "Usa la catena predefinita di provider di credenziali AWS" è abilitato sopra.';
$string['usedefaultcredentialchain'] = 'Usa la catena predefinita di provider di credenziali AWS';
$string['usedefaultcredentialchain_desc'] = 'Abilita questa opzione se Moodle è in esecuzione in un ambiente AWS in cui le credenziali IAM sono disponibili automaticamente.';
$string['userratelimit'] = 'Numero massimo di richieste per utente';
$string['userratelimit_desc'] = 'Il numero di richieste consentite all\'ora, per utente.';
