Feature:
    Ability to browse and manage the subfolders of a folder of the media library from the files list

    Scenario: As an owner I see and manage the subfolders of the folder I am in
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Facturas" with id "f-2" of account "1111" exists
        Given subfolder "2025" with id "s-2" in folder "f-1" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        Given subfolder "Abril" with id "s-3" in folder "f-2" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        Then I do not see a text "Subcarpetas de"
        When I filter the library by the folder "Contratos"
        Then the response status should be 200
        Then I see the folder tiles "Sin carpeta, Contratos, Facturas, Todas las carpetas"
        Then the folder tile "Contratos" is active
        Then I see a text "Subcarpetas de Contratos"
        Then I see a text "Nueva subcarpeta"
        Then I see a text "library/folders/register?parent=f-1"
        Then I see the subfolder tiles "Sin subcarpeta, 2024, 2025, Todas las subcarpetas" of the folder "Contratos"
        Then the subfolder tile "Sin subcarpeta" is active
        Then I see a text "library/folders/edit/s-1"
        Then I see a text "library/folders/remove/s-1"
        Then I do not see a text "library/folders/edit/s-3"
        Then I see a text "Contratos / Todas las subcarpetas</option>"
        Then I see a text "Facturas / Todas las subcarpetas</option>"

    Scenario: As a user I see the subfolders but I cannot manage them
        Given account "Minotour" with id "1111" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media"
        When I filter the library by the folder "Contratos"
        Then the response status should be 200
        Then I see the subfolder tiles "Sin subcarpeta, 2024, Todas las subcarpetas" of the folder "Contratos"
        Then I do not see a text "Nueva subcarpeta"
        Then I do not see a text "library/folders/edit/"
        Then I do not see a text "library/folders/remove/"

    Scenario: As an owner inside a folder without subfolders I am invited to create one
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        When I filter the library by the folder "Contratos"
        Then the response status should be 200
        Then I see a text "Subcarpetas de Contratos"
        Then I see a text "Esta carpeta todavía no tiene subcarpetas"
        Then I see a text "library/folders/register?parent=f-1"
        Then I do not see a text "Sin subcarpeta"

    Scenario: As a user inside a folder without subfolders I do not see the second strip
        Given account "Minotour" with id "1111" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/media"
        When I filter the library by the folder "Contratos"
        Then the response status should be 200
        Then I do not see a text "Subcarpetas de"
        Then I do not see a text "Esta carpeta todavía no tiene subcarpetas"

    Scenario: Without folder and with all the folders there is no second strip
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        Then I do not see a text "Subcarpetas de"
        Then I see the folder tiles "Sin carpeta, Contratos, Todas las carpetas"
        When I filter the library by all the folders
        Then the response status should be 200
        Then I do not see a text "Subcarpetas de"
        Then I do not see a text "Nueva subcarpeta"

    Scenario: Entering a subfolder lists only its files and highlights both tiles
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        Given subfolder "2025" with id "s-2" in folder "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        Given media "contract-2024.png" with id "m-2" in folder "s-1" of account "1111" exists
        Given media "contract-2025.png" with id "m-3" in folder "s-2" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        When I filter the library by the folder "Contratos"
        Then I see a text "signed.png"
        Then I do not see a text "contract-2024.png"
        Then I do not see a text "contract-2025.png"
        When I filter the library by the subfolder "Contratos / 2024"
        Then the response status should be 200
        Then I see a text "contract-2024.png"
        Then I do not see a text "signed.png"
        Then I do not see a text "contract-2025.png"
        Then the folder tile "Contratos" is active
        Then the subfolder tile "2024" is active
        Then I see a text "Biblioteca &gt; Contratos &gt; 2024"
        Then the active filters show "Carpeta: Contratos / 2024"
        Then I see a text "Contratos / 2024</td>"
        Then I see a text "library/media/upload?folder=s-1"

    Scenario: All the subfolders lists the files of the folder and of its subfolders
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Facturas" with id "f-2" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        Given media "contract-2024.png" with id "m-2" in folder "s-1" of account "1111" exists
        Given media "invoice.png" with id "m-3" in folder "f-2" of account "1111" exists
        Given media "logo.png" with id "m-4" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        When I filter the library by all the subfolders of "Contratos"
        Then the response status should be 200
        Then I see a text "signed.png"
        Then I see a text "contract-2024.png"
        Then I do not see a text "invoice.png"
        Then I do not see a text "logo.png"
        Then the folder tile "Contratos" is active
        Then the subfolder tile "Todas las subcarpetas" is active
        Then I see a text "Biblioteca &gt; Contratos &gt; Todas las subcarpetas"
        Then the active filters show "Carpeta: Contratos / Todas las subcarpetas"

    Scenario: All the subfolders falls back to the folder when its last subfolder is removed
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        Given media "signed.png" with id "m-1" in folder "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        When I filter the library by all the subfolders of "Contratos"
        Then the subfolder tile "Todas las subcarpetas" is active
        When I go to "/es/library/folders/remove/s-1" with csrf token "remove"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then the folder tile "Contratos" is active
        Then I see a text "Biblioteca &gt; Contratos"
        Then I do not see a text "Todas las subcarpetas"
        Then I see a text "signed.png"

    Scenario: All the subfolders falls back to the files without folder when the folder is removed
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Facturas" with id "f-2" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        Given media "logo.png" with id "m-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/media"
        When I filter the library by all the subfolders of "Contratos"
        When I go to "/es/library/folders/remove/f-1" with csrf token "remove"
        When I go to "/es/library/media"
        Then the response status should be 200
        Then the folder tile "Sin carpeta" is active
        Then I do not see a text "Subcarpetas de"
        Then I see a text "logo.png"

    Scenario: While impersonating an account I see the subfolders of that account
        Given account "AdminAccount" with id "admin-account-id" exists
        Given account "Acme" with id "a1" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "admin-account-id" exists
        Given folder "Contratos Acme" with id "f-2" of account "a1" exists
        Given subfolder "2024 Acme" with id "s-2" in folder "f-2" of account "a1" exists
        Given folder "Propia" with id "f-1" of account "admin-account-id" exists
        Given subfolder "2024 Propia" with id "s-1" in folder "f-1" of account "admin-account-id" exists
        When I am logged as "admin@dediez.software"
        When I impersonate account "Acme"
        When I go to "/es/library/media"
        When I filter the library by the folder "Contratos Acme"
        Then I see the subfolder tiles "Sin subcarpeta, 2024 Acme, Todas las subcarpetas" of the folder "Contratos Acme"
        Then I do not see a text "2024 Propia"
