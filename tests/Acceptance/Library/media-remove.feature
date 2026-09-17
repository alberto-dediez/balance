Feature:
    Ability to remove a file from the media library

    Scenario: As an admin I can remove a file
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given media "logo.png" with id "1111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/library/media/remove/1111" with csrf token "remove"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado el archivo"
        Then the library contains 0 files

    Scenario: As a user I cannot remove a file of another account
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "2222" exists
        Given media "logo.png" with id "3333" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media/remove/3333" with csrf token "remove"
        Then the response status should be 403
        Then the library contains 1 files
