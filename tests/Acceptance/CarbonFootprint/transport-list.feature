Feature:
    Ability to list transports in web

    Scenario: As admin I can access a list of all transports
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given transport "Transport 1" with id "1111" exists
        Given transport "Transport 2" with id "2222" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/transports"
        Then the response status should be 200
        Then I see a table with 4 rows

    Scenario: As normal user I can't access a list of all transports
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given transport "Transport 1" with id "1111" exists
        Given transport "Transport 2" with id "2222" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/admin/transports"
        Then the response status should be 403

    Scenario: As admin I can filter transports by mode
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given transport "Tren de contenedores" with id "1111" in unit "tkm" with vehicle "freight_train" and emission factor for year "2025" exists
        Given transport "Camión rígido" with id "2222" in unit "kg" with vehicle "trucks" and emission factor for year "2025" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/transports"
        When I submit a form with "mode:rail" and button "list_transports_filter"
        Then the response status should be 200
        Then I see a text "Tren de contenedores"
        Then I see a text "Ferrocarril"
        Then I do not see a text "Camión rígido"
