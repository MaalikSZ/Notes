<?php
class NotesApp {
    private $db_connection;
    private $file_path;

    const DB_HOST = '  ';
    const DB_USERNAME = '   ';
    const DB_PASSWORD = '  ';
    const DB_NAME = ' ';
    const MAX_NOTE_LENGTH = 100;

    public function __construct($file_path) {
        $this->file_path = $file_path;
        $this->db_connection = new mysqli(self::DB_HOST, self::DB_USERNAME, self::DB_PASSWORD, self::DB_NAME);
        if ($this->db_connection->connect_errno) {
            throw new Exception("Błąd połączenia z bazą danych: " . $this->db_connection->connect_error);
        }
    }

    public function displayNotes() {
        $notes = $this->loadNotes();

        if (empty($notes)) {
            echo "<p>Brak notatek.</p>";
        } else {
            echo "<h2>Notatki:</h2>";
            echo "<ul>";
            foreach ($notes as $note) {
                echo "<li>" . htmlspecialchars($note) . "</li>";
            }
            echo "</ul>";
        }
    }

    public function addNote($note) {
        try {
            $this->validateNoteInput($note);
            $this->validateNoteLength($note);
            $note = $this->db_connection->real_escape_string($note);

            $query = "INSERT INTO notes (note) VALUES ('$note')";
            if (!$this->db_connection->query($query)) {
                throw new Exception("Błąd podczas dodawania notatki do bazy danych: " . $this->db_connection->error);
            }

            return true;
        } catch (Exception $e) {
            throw new Exception("Nie udało się dodać notatki: " . $e->getMessage());
        }
    }

    private function loadNotes() {
        if (!$this->isFileAccessible()) {
            return [];
        }

        $query = "SELECT note FROM notes";
        $result = $this->db_connection->query($query);
        if (!$result) {
            throw new Exception("Błąd podczas pobierania notatek z bazy danych: " . $this->db_connection->error);
        }

        $notes = [];
        while ($row = $result->fetch_assoc()) {
            $notes[] = $row['note'];
        }

        return $notes;
    }

    private function isFileAccessible() {
        if (!file_exists($this->file_path)) {
            throw new Exception("Plik notatek nie istnieje.");
        }

        if (!is_readable($this->file_path) || !is_writable($this->file_path)) {
            throw new Exception("Plik notatek nie jest dostępny do odczytu lub zapisu.");
        }

        return true;
    }

    private function validateNoteInput($note) {
        if (!isset($note) || empty(trim($note))) {
            throw new Exception("Notatka nie może być pusta.");
        }

        if (!ctype_alnum($note)) {
            throw new Exception("Notatka zawiera nieprawidłowe znaki.");
        }

        return true;
    }

    private function validateNoteLength($note) {
        if (strlen($note) > self::MAX_NOTE_LENGTH) {
            throw new Exception("Notatka przekracza maksymalną długość " . self::MAX_NOTE_LENGTH . " znaków.");
        }

        return true;
    }
}

try {
    $notesApp = new NotesApp("notes.txt");

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["note"])) {
        $note = $_POST["note"];
        if ($notesApp->addNote($note)) {
            echo "<p>Notatka została dodana.</p>";
        } else {
            echo "<p>Nie udało się dodać notatki.</p>";
        }
    }

    $notesApp->displayNotes();
} catch (Exception $e) {
    echo "<p>Wystąpił błąd: " . $e->getMessage() . "</p>";
}
?>