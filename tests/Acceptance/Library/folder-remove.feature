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

    Scenario: The files of a removed folder are removed
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/remove/f-1" with csrf token "remove"
        Then the media "m-1" is removed
        When I go to "/es/library/media"
        Then the response status should be 200
        When I filter the library by all the folders
        Then I do not see a text "signed.png"

    Scenario: Removing a subfolder removes its files and keeps the files of its folder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        Given subfolder "2025" with id "s-2" in folder "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        Given media "contract-2024.png" with id "m-2" in folder "s-1" of account "1111" exists
        Given media "contract-2025.png" with id "m-3" in folder "s-2" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/remove/s-1" with csrf token "remove"
        Then the response redirects to "/es/library/media"
        When I follow redirect
        Then I see a text "Se ha eliminado la carpeta 2024"
        Then the folder "2024" does not exist
        Then the folder "Contratos" exists
        Then the folder "2025" exists
        Then the media "m-2" is removed
        Then the media "m-1" is not removed
        Then the media "m-3" is not removed

    Scenario: Removing a folder removes its subfolders and all their files
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        Given media "contract-2024.png" with id "m-2" in folder "s-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/remove/f-1" with csrf token "remove"
        Then the response redirects to "/es/library/media"
        When I follow redirect
        Then I see a text "Se ha eliminado la carpeta Contratos"
        Then the folder "Contratos" does not exist
        Then the folder "2024" does not exist
        Then the media "m-1" is removed
        Then the media "m-2" is removed

    Scenario: A folder whose files cannot be removed is kept
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        Given the main media storage is unavailable
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/remove/f-1" with csrf token "remove"
        Then the response redirects to "/es/library/media"
        Then the folder "Contratos" exists
        Then the media "m-1" is not removed
        Then the media "m-1" is in the folder "f-1"
        When I follow redirect
        Then I see a text "No se ha podido eliminar la carpeta Contratos porque alguno de sus archivos no se ha podido eliminar"

    Scenario: The remove confirmation warns that the files are removed too
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        When I filter the library by the folder "Contratos"
        Then I see a text "Se eliminarán también sus subcarpetas y todos los archivos que contiene"
        Then I see a text "Se eliminarán también todos los archivos que contiene"

    Scenario: As a user I cannot remove folders
        Given account "Minotour" with id "1111" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/folders/remove/f-1" with csrf token "remove"
        Then the response status should be 403
        Then the folder "Contratos" exists
