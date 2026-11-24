Feature:
    A daily backup copies the library files to a second storage

    Scenario: The backup copies the library files to the backup storage
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given media "logo.png" with id "1111" exists
        Given document "contract.pdf" with id "2222" exists
        Given I am the system
        When command "library:backup:run" is executed
        Then Is Executed Successfully
        Then the command output shows 2 for "copiados"
        Then the command output shows 0 for "errores"
        Then the backup storage contains media "1111"
        Then the backup storage contains media "2222"

    Scenario: A second backup copies nothing
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given media "logo.png" with id "1111" exists
        Given I am the system
        When command "library:backup:run" is executed
        When command "library:backup:run" is executed
        Then Is Executed Successfully
        Then the command output shows 0 for "copiados"
        Then the command output shows 1 for "ya copiados"
        Then the backup storage contains media "1111"

    Scenario: Removing a file from the library keeps its backup
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given media "logo.png" with id "1111" exists
        Given I am the system
        When command "library:backup:run" is executed
        When I go to "/es/library/media/remove/1111" with csrf token "remove"
        Then the library contains 0 files
        Then the backup of media "1111" is marked as removed
        Then the backup storage contains media "1111"

    Scenario: The backup of a file removed 31 days ago is purged
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given media "logo.png" with id "1111" exists
        Given I am the system
        When command "library:backup:run" is executed
        When I go to "/es/library/media/remove/1111" with csrf token "remove"
        Given the backup of media "1111" was removed 31 days ago
        When command "library:backup:run" is executed
        Then Is Executed Successfully
        Then the command output shows 1 for "purgados"
        Then the backup storage does not contain media "1111"

    Scenario: The backup of a file removed 10 days ago is kept
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given media "logo.png" with id "1111" exists
        Given I am the system
        When command "library:backup:run" is executed
        When I go to "/es/library/media/remove/1111" with csrf token "remove"
        Given the backup of media "1111" was removed 10 days ago
        When command "library:backup:run" is executed
        Then Is Executed Successfully
        Then the backup storage contains media "1111"

    Scenario: A failed backup warns the super admins
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "superadmin@dediez.software" with role "ROLE_SUPER_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given media "logo.png" with id "1111" exists
        Given the backup media storage is unavailable
        When I go to "/es/library/media/edit/1111"
        Given I am the system
        When command "library:backup:run" is executed
        Then I expect status code 1
        Then the command output shows 1 for "errores"
        Then user "superadmin@dediez.software" has 1 notifications with subject "La copia de seguridad de la biblioteca ha fallado"
        Then user "admin@dediez.software" has 0 notifications with subject "La copia de seguridad de la biblioteca ha fallado"

    Scenario: Restoring brings back a file missing in the main storage
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given media "logo.png" with id "1111" exists
        Given I am the system
        When command "library:backup:run" is executed
        Given the file of media "1111" is missing in the "local" storage
        When command "library:backup:restore" is executed
        Then Is Executed Successfully
        Then the command output shows 1 for "restaurados"
        Then media "1111" is stored in the "local" storage

    Scenario: Extracting the backup of a removed file to a local file
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given media "logo.png" with id "1111" exists
        Given I am the system
        When command "library:backup:run" is executed
        When I go to "/es/library/media/remove/1111" with csrf token "remove"
        When command "library:backup:restore" is executed with argument "--media:1111 --output:/tmp/behat-media-backup-1111.png"
        Then Is Executed Successfully
        Then I want to see "Archivo extraído en /tmp/behat-media-backup-1111.png" in command output
        Then the file "/tmp/behat-media-backup-1111.png" exists with the contents of media "1111"

    Scenario: The output option requires a media
        Given I am the system
        When command "library:backup:restore" is executed with argument "--output:/tmp/behat-media-backup.png"
        Then I expect status code 2
