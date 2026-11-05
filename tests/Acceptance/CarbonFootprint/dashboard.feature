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
        Then I see a text "CO₂eq compensado"
        Then I see a text "Fondos canalizados"
        Then I see a text "Participación voluntaria"
        Then I see a text "Respuestas de asistentes"
        Then I see a text "Últimas huellas de carbono"
        Then I see a text "Aún no hay huellas organizacionales"

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
        Then I see a text "CO₂eq compensado"
        Then I see a text "Fondos canalizados"
        Then I see a text "Participación voluntaria"
        Then I see a text "Cálculos de huéspedes"
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

    Scenario: As owner without events I do not see the events blocks of my dashboard
        Given account "MyCompany" with id "666" exists
        Given account "666" has feature "events" disabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "Panel de control"
        Then I see a text "CO₂eq compensado"
        Then I see a text "Cálculos de huéspedes"
        Then I do not see a text "Huellas completadas"
        Then I do not see a text "Registrar evento"
        Then I do not see a text "Respuestas de asistentes"
        Then I do not see a text "Últimas huellas de carbono"

    Scenario: As owner without compensation I do not see the compensation blocks of my dashboard
        Given account "MyCompany" with id "666" exists
        Given account "666" has feature "compensation" disabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "1111" of account "666" exists
        Given event carbon footprint for event "1111" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "Huellas completadas"
        Then I do not see a text "CO₂eq compensado"
        Then I do not see a link to "/es/compensate/account/666"

    Scenario: As owner without any feature I see a notice in my dashboard
        Given account "MyCompany" with id "666" exists
        Given account "666" has feature "events" disabled
        Given account "666" has feature "compensation" disabled
        Given account "666" has feature "account_trip_calculator" disabled
        Given account "666" has feature "property_trip_calculators" disabled
        Given account "666" has feature "organizational_carbon_footprint" disabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "No hay estadísticas disponibles para las funcionalidades de esta cuenta."
        Then I do not see a text "Huellas completadas"
        Then I do not see a text "CO₂eq compensado"
        Then I do not see a text "Huella organizacional"

    Scenario: As owner with organizational carbon footprints I see their statistics in my dashboard
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel A" with id "p1" of account "666" exists
        Given property carbon footprint with id "f1" for property "p1" of account "666" and year 2025 with employees answered exists
        Given property carbon footprint "f1" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "Huella organizacional"
        Then I see a text "Propiedades con huella"
        Then I see a text "Emisiones 2025"
        Then I see a text "Últimas huellas organizacionales"
        Then I see a text "Hotel A"
        Then I see a link to "/es/property-carbon-footprints/f1/step/results"

    Scenario: As owner I see links to the results of completed company footprints and to the wizard of footprints in progress
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel A" with id "p1" of account "666" exists
        Given company "Hotelera E" with id "c1" of account "666" exists
        Given company carbon footprint with id "e1" for company "c1" of account "666" and year 2025 with employees answered exists
        Given company carbon footprint "e1" is completed
        Given property carbon footprint with id "f2" for property "p1" of account "666" and year 2025 exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "Hotelera E"
        Then I see a link to "/es/company-carbon-footprints/e1/step/results"
        Then I see a link to "/es/property-carbon-footprints/f2/edit"

    Scenario: As owner without organizational carbon footprint feature I do not see its statistics
        Given account "MyCompany" with id "666" exists
        Given account "666" has feature "organizational_carbon_footprint" disabled
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "1111" of account "666" exists
        Given event carbon footprint for event "1111" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "Huellas completadas"
        Then I do not see a text "Huella organizacional"

    Scenario: As owner without events but with organizational carbon footprint I see the organizational block
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel A" with id "p1" of account "666" exists
        Given property carbon footprint with id "f1" for property "p1" of account "666" and year 2025 with employees answered exists
        Given property carbon footprint "f1" is completed
        When I am logged as "owner@dediez.software"
        When I go to "/es/"
        Then the response status should be 200
        Then I see a text "Aún no hay datos que mostrar"
        Then I see a text "Registrar evento"
        Then I see a text "Huella organizacional"
        Then I see a text "CO₂eq compensado"
