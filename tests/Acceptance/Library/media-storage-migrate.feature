Feature:
    I want to migrate the library files from one storage to another from command line

    Scenario: Command library:storage:migrate copies the existing files to the OVH storage
        Given I am the system
        Given media "logo.png" with id "1111" exists
        Given document "contract.pdf" with id "2222" exists
        When command "library:storage:migrate" is executed with argument "--from:local --to:ovh"
        Then Is Executed Successfully
        Then I want to see "migrados" in command output
        Then I want to see "2" in command output
        Then media "1111" is stored in the "ovh" storage
        Then media "2222" is stored in the "ovh" storage
        Then media "1111" is stored in the "local" storage
        Then media "2222" is stored in the "local" storage

    Scenario: Repeating the migration copies nothing
        Given I am the system
        Given media "logo.png" with id "1111" exists
        Given document "contract.pdf" with id "2222" exists
        When command "library:storage:migrate" is executed with argument "--from:local --to:ovh"
        When command "library:storage:migrate" is executed with argument "--from:local --to:ovh"
        Then Is Executed Successfully
        Then I want to see "ya en destino" in command output
        Then media "1111" is stored in the "ovh" storage
        Then media "2222" is stored in the "ovh" storage

    Scenario: The source files are deleted when asked
        Given I am the system
        Given media "logo.png" with id "1111" exists
        When command "library:storage:migrate" is executed with argument "--from:local --to:ovh --delete-source:1"
        Then Is Executed Successfully
        Then media "1111" is stored in the "ovh" storage
        Then media "1111" is not stored in the "local" storage

    Scenario: The files missing in the source storage are reported
        Given I am the system
        Given media "logo.png" with id "1111" exists
        Given the file of media "1111" is missing in the "local" storage
        When command "library:storage:migrate" is executed with argument "--from:local --to:ovh"
        Then Is Executed Successfully
        Then I want to see "no encontrados en origen" in command output
        Then media "1111" is not stored in the "ovh" storage

    Scenario: The source and target storages must differ
        Given I am the system
        When command "library:storage:migrate" is executed with argument "--from:local --to:local"
        Then I expect status code 2
