<?php
use EDAM\Types\Data, EDAM\Types\Note;
use EDAM\Error\EDAMUserException, EDAM\Error\EDAMErrorCode;
use Evernote\Client;

require_once 'autoload.php';
require_once 'Evernote/Client.php';
require_once 'packages/Errors/Errors_types.php';
require_once 'packages/Types/Types_types.php';
require_once 'packages/Limits/Limits_constants.php';

function help() {
echo "\nThis is a command line PHP script with the following options:
    Usage:
    " . basename(__FILE__) . " title cli|file \"text\"|\"/path/to/file\"
    title: Title of note
    cli: to be followed by \"text\", e.g:
      " . basename(__FILE__) . " cli \"Note title\" \"Add to note\"
    file: must be followed by a path, e.g.:
      " . basename(__FILE__) . " file \"Another title\" \"/home/user/notes.txt\"

    Both the cli and file options accept HTML tags as parameters, e.g.:
      " . basename(__FILE__) . " cli \"My Title\" \"I can accept <strong>HTML tags</strong>\"\n
";
}

function en_exception_handler($exception) {
    echo "Uncaught " . get_class($exception) . ":\n";
    if ($exception instanceof EDAMUserException) {
        echo "Error code: " . EDAMErrorCode::$__names[$exception->errorCode] . "\n";
        echo "Parameter: " . $exception->parameter . "\n";
    } elseif ($exception instanceof EDAMSystemException) {
        echo "Error code: " . EDAMErrorCode::$__names[$exception->errorCode] . "\n";
        echo "Message: " . $exception->message . "\n";
    } else {
        echo $exception;
    }
}
set_exception_handler('en_exception_handler');

function addNote($title, $content) {
    //Get token from:https://sandbox.evernote.com/api/DeveloperToken.action or https://wwww.evernote.com/api/DeveloperToken.action
    $authToken = "";
    $client = new Client(array('token' => $authToken));
    
    $noteStore = $client->getNoteStore();
    $note = new Note();
    $note->title = $title;
    // The content of an Evernote note is represented using Evernote Markup Language
    // (ENML). The full ENML specification can be found in the Evernote API Overview
    // at http://dev.evernote.com/documentation/cloud/chapters/ENML.php
    $note->content =
        '<?xml version="1.0" encoding="UTF-8"?>' .
        '<!DOCTYPE en-note SYSTEM "http://xml.evernote.com/pub/enml2.dtd">' .
        '<en-note>' . nl2br($content) . '<br/></en-note>';

    // When note titles are user-generated, it's important to validate them
    $len = strlen($note->title);
    $min = $GLOBALS['EDAM_Limits_Limits_CONSTANTS']['EDAM_NOTE_TITLE_LEN_MIN'];
    $max = $GLOBALS['EDAM_Limits_Limits_CONSTANTS']['EDAM_NOTE_TITLE_LEN_MAX'];
    $pattern = '#' . $GLOBALS['EDAM_Limits_Limits_CONSTANTS']['EDAM_NOTE_TITLE_REGEX'] . '#'; // Add PCRE delimiters
    if ($len < $min || $len > $max || !preg_match($pattern, $note->title)) {
        print "\nInvalid note title: " . $note->title . '\n\n';
        exit(1);
    }
    $createdNote = $noteStore->createNote($note);

    print "Successfully created a new note:  " .  $note->title . " (GUID: " . $createdNote->guid . ")\n";

}

if ($argc != 4 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
    help();
    exit();
} else {
    $mode = isset($argv[1]) ? $argv[1] : undef;
    $title = isset($argv[2]) ? $argv[2] : undef;
    $content = isset($argv[3]) ? $argv[3] : undef;

    if (!isset($mode) || !isset($title) || !isset($title)) {
        help();
    }

    if ($mode == "cli") {
        addNote($title, $content);
    }
    elseif ($mode == "file") {
        //parse file
        if (!file_exists($content)) {
            echo "The file defined cannot be found - make sure you entered the right file (" . $content . ") and that you're using the right parameter ('file').";
            help();
            exit();
        } else {
            $content = file_get_contents($content);
            addnote($title, $content);
        }
    }
    else {
        help();
        exit();
    }
}
