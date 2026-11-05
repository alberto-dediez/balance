Feature:
    Ability to export the properties list as spreadsheet files

    Scenario: As an owner I see the export button in my properties list
        Given account "Mi cuenta" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "777" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/properties"
        Then the response status should be 200
        Then I see a link with name "Exportar"

    Scenario: As an owner the export button carries the current list filters
        Given account "Mi cuenta" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "777" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/properties"
        When I filter by "name" with "Hotel"
        Then the response status should be 200
        Then I see a link to "/es/properties/export?name=Hotel&orderBy=default&orderWay=ASC"

    Scenario: As an owner I open the properties export with the list filters
        Given account "Mi cuenta" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given property "Hotel" with id "777" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/properties/export?name=Hotel"
        Then the response status should be 200
        Then I see a form field "[filter][name]" with value "Hotel"
        When I submit a form with "[filter][name]:Hotel" and button "Exportar"
        Then I see a text "La exportación se está generando"

    Scenario: As a distributor owner without property features I can export my properties list
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        Given account "555" has feature "events" disabled
        Given account "555" has feature "organizational_carbon_footprint" disabled
        Given account "555" has feature "property_trip_calculators" disabled
        Given user "owner@greenhost.test" with role "ROLE_OWNER" of account "555" exists
        Given property "Sede Greenhost" with id "10" of account "555" exists
        When I am logged as "owner@greenhost.test"
        When I go to "/es/properties/export"
        Then the response status should be 200
        When I submit a form with "[filter][name]:" and button "Exportar"
        Then I see a text "La exportación se está generando"

    Scenario: As a normal user I cannot access the properties export
        Given account "Mi cuenta" with id "666" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "666" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/properties/export"
        Then the response status should be 403
