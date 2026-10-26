Feature:
    The library keeps working with the local disk while the main storage is unavailable

    Scenario: Uploading while the main storage is unavailable stores the file locally and warns the admins
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given the main media storage is unavailable
        When I go to "/es/library/media/upload"
        When I submit the upload form with file "logo.png"
        When I follow redirect
        Then I see a text "Archivo subido correctamente"
        Then the library contains 1 files
        Then the last uploaded media is stored in the fallback storage
        Then there are 1 pending storage sync tasks
        Then user "admin@dediez.software" has 1 notifications with subject "El almacenamiento de la biblioteca no está disponible"
        Given the main media storage is available again
        Then the last uploaded media is not stored in the main storage

    Scenario: A second upload during the same outage does not warn again
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given the main media storage is unavailable
        When I go to "/es/library/media/upload"
        When I submit the upload form with file "logo.png"
        When I go to "/es/library/media/upload"
        When I submit the upload form with file "other.png"
        Then the library contains 2 files
        Then there are 2 pending storage sync tasks
        Then user "admin@dediez.software" has 1 notifications with subject "El almacenamiento de la biblioteca no está disponible"

    Scenario: A file kept locally can be downloaded while the main storage is unavailable
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given the main media storage is unavailable
        When I go to "/es/library/media/upload"
        When I submit the upload form with file "logo.png"
        When I go to the show page of the last uploaded media
        Then the response status should be 200

    Scenario: A file that only exists in the main storage is not available during the outage
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given media "logo.png" with id "1111" exists
        Given the main media storage is unavailable
        When I go to "/es/library/media/1111/show"
        Then the response status should be 404

    Scenario: Removing a file from the main storage fails while it is unavailable
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given media "logo.png" with id "1111" exists
        Given the main media storage is unavailable
        When I go to "/es/library/media/remove/1111" with csrf token "remove"
        When I follow redirect
        Then I see a text "No se ha podido eliminar el archivo logo.png"
        Then there are 0 pending storage sync tasks

    Scenario: Removing a file kept locally works during the outage
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given the main media storage is unavailable
        When I go to "/es/library/media/upload"
        When I submit the upload form with file "logo.png"
        When I go to the remove page of the last uploaded media
        Then the library contains 0 files
        Then there are 0 pending storage sync tasks
        Then the last removed media is not stored in the fallback storage

    Scenario: Pending files are uploaded to the main storage when it is available again
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given the main media storage is unavailable
        When I go to "/es/library/media/upload"
        When I submit the upload form with file "logo.png"
        Given I am the system
        Given the main media storage is available again
        When command "library:storage:sync" is executed
        Then Is Executed Successfully
        Then I want to see "subidos" in command output
        Then there are 0 pending storage sync tasks
        Then the last uploaded media is stored in the main storage
        Then the last uploaded media is not stored in the fallback storage
        Then user "admin@dediez.software" has 1 notifications with subject "El almacenamiento de la biblioteca se ha recuperado"

    Scenario: The sync stops at the first failure when the main storage is still unavailable
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given the main media storage is unavailable
        When I go to "/es/library/media/upload"
        When I submit the upload form with file "logo.png"
        Given I am the system
        When command "library:storage:sync" is executed
        Then I expect status code 1
        Then there are 1 pending storage sync tasks
        Then I want to see "pendientes" in command output

    Scenario: Listing the pending files
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        Given the main media storage is unavailable
        When I go to "/es/library/media/upload"
        When I submit the upload form with file "logo.png"
        Given I am the system
        When command "library:storage:sync" is executed with argument "--list:1"
        Then Is Executed Successfully
        Then I want to see "shared/" in command output
        Then there are 1 pending storage sync tasks
