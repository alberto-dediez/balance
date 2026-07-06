Feature:
    Ability to see the owner dashboard in web

    Scenario: As owner I can see my dashboard in the home page
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "1111" of account "666" exists
        Given event carbon footprint for event "1111" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "Panel de control"
        Then I see a text "Huellas completadas"
        Then I see a text "Últimas huellas de carbono"

    Scenario: As admin I can see the platform dashboard in the home page
        Given account "MyCompany" with id "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given event "Evento A" with id "1111" of account "666" exists
        Given event carbon footprint for event "1111" of account "666" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "Panel de control"
        Then I see a text "Cuentas con más emisiones"
        Then I see a text "Últimas huellas de carbono"

    Scenario: As owner without account I see the standard home page
        Given user "owner@dediez.software" with role "ROLE_OWNER" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "Aún no disponemos de info para el dashboard inicial"

    Scenario: As normal user I see the standard home page
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "Aún no disponemos de info para el dashboard inicial"
