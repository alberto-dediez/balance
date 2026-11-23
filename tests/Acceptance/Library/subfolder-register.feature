Feature:
    Ability to register subfolders inside the folders of the media library

    Scenario: As an owner I can register a subfolder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/register?parent=f-1"
        Then the response status should be 200
        Then I see a text "Biblioteca &gt; Contratos &gt; Nueva subcarpeta"
        When I submit a form with "name:2024" and button "Crear"
        Then the response redirects to "/es/library/media"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Subcarpeta creada con éxito"
        Then the folder "2024" exists

    Scenario: A subfolder cannot repeat the name of another subfolder of the same folder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/register?parent=f-1"
        When I submit a form with "name:2024" and button "Crear"
        Then the response status should be 200
        Then I see a text "Ya existe una subcarpeta con ese nombre en esta carpeta"
        Then I do not see a text "Subcarpeta creada con éxito"

    Scenario: A subfolder can be named as a subfolder of another folder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Facturas" with id "f-2" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/register?parent=f-2"
        When I submit a form with "name:2024" and button "Crear"
        Then the response redirects to "/es/library/media"
        When I follow redirect
        Then I see a text "Subcarpeta creada con éxito"

    Scenario: A subfolder can be named as a folder
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given folder "Facturas" with id "f-2" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/register?parent=f-1"
        When I submit a form with "name:Facturas" and button "Crear"
        Then the response redirects to "/es/library/media"
        When I follow redirect
        Then I see a text "Subcarpeta creada con éxito"

    Scenario: A subfolder cannot contain subfolders
        Given account "Minotour" with id "1111" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        Given subfolder "2024" with id "s-1" in folder "f-1" of account "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/register?parent=s-1"
        Then the response status should be 404

    Scenario: The parent folder must be a known folder of my account
        Given account "Minotour" with id "1111" exists
        Given account "Bioscore" with id "2222" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "1111" exists
        Given folder "Ajena" with id "f-3" of account "2222" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/library/folders/register?parent=f-3"
        Then the response status should be 404
        When I go to "/es/library/folders/register?parent=f-9"
        Then the response status should be 404

    Scenario: As a user I cannot register subfolders
        Given account "Minotour" with id "1111" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "1111" exists
        Given folder "Contratos" with id "f-1" of account "1111" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/library/folders/register?parent=f-1"
        Then the response status should be 403
