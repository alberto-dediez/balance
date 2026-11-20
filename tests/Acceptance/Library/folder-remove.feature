Feature:
    Ability to remove the folders of the media library

    Scenario: As an owner I can remove a folder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/remove/f-1" with csrf token "remove"
        Then the response redirects to "/es/library/media"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la carpeta Contratos"
        Then the folder "Contratos" does not exist

    Scenario: A folder cannot be removed without the csrf token
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/remove/f-1"
        Then the response status should be 403
        Then the folder "Contratos" exists

    Scenario: The files of a removed folder stay in the library without folder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/remove/f-1" with csrf token "remove"
        Then the media "m-1" is not in any folder
        When I go to "/es/library/media"
        Then the response status should be 200
        Then I see a text "signed.png"

    Scenario: As a user I cannot remove folders
        Given account "Minotour" with id "1111" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/folders/remove/f-1" with csrf token "remove"
        Then the response status should be 403
        Then the folder "Contratos" exists
