Feature:
    Ability to upload new files to the media library

    Scenario: As a user I can access the upload form
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/upload"
        Then the response status should be 200
        Then I see a form

    Scenario: As a user I can upload a new file
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/upload"
        When I submit the upload form with file "picture.png"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Archivo subido correctamente"
        Then the library contains 1 files

    Scenario: As a user I decide the visibility of the files I upload
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/upload"
        Then I see a text "Visibilidad"

    Scenario: As a user I cannot share the files I upload with the other accounts
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/upload"
        Then the response status should be 200
        Then I do not see a text "Compartir en la biblioteca"

    Scenario: As an admin I can share the files I upload with the other accounts
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/library/media/upload"
        Then the response status should be 200
        Then I see a text "Compartir en la biblioteca"

    Scenario: As a user I can upload several files at once
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/upload"
        When I submit the upload form with files "picture.png, other-picture.png"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Archivos subidos correctamente"
        Then the library contains 2 files

    Scenario: The upload form offers a drop zone
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/upload"
        Then the response status should be 200
        Then I see a text "Arrastra aquí los archivos o pulsa para elegirlos"
