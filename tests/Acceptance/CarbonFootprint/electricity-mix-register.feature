Feature:
    I want to make sure that the software allow to register an electricity mix

    Scenario: As an admin, I can access to register new electricity mixes
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-mixes/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new electricity mixes
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/electricity-mixes/register"
        Then the response status should be 403

    Scenario: As an admin, I can register new electricity mixes
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-mixes/register"
        When I submit a form with "countryId:C-ES" and button "Registrar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Mix eléctrico creado correctamente: España"

    Scenario: After register a new electricity mix, I am redirected to edit it
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-mixes/register"
        When I submit a form with "countryId:C-ES" and button "Registrar"
        Then I am redirected to route "admin_electricity_mixes_edit"

    Scenario: As an admin, I can't register a second electricity mix for the same country
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given country "España" with iso "ES" and id "C-ES" exists
        Given electricity mix for country "C-ES" with id "1111" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-mixes/register"
        When I submit a form with "countryId:C-ES" and button "Registrar"
        Then the response status should be 200
        Then I see a text "Ya existe un mix eléctrico para ese país"
        When I go to "/es/admin/electricity-mixes"
        Then I see a table with 3 rows
