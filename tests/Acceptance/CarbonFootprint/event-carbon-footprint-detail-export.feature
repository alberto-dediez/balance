Feature:
    Ability to export the detail of an event carbon footprint as spreadsheet files

    Scenario: As an owner I can export the detail of a completed footprint of my account
        Given account "Mi cuenta" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "ECF1" for event "333" of account "666" completed exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/event_carbon_footprint_detail/export?eventCarbonFootprintId=ECF1"
        Then the response status should be 200
        Then I see a form field "[filter][eventCarbonFootprintId]" with value "ECF1"
        When I submit a form with "[filter][eventCarbonFootprintId]:ECF1" and button "Exportar"
        Then I see a text "La exportación se está generando"

    Scenario: As an owner I cannot export a footprint that is not completed
        Given account "Mi cuenta" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento B" with id "444" of account "666" exists
        Given event carbon footprint with id "ECF2" for event "444" of account "666" with breakdown answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/event_carbon_footprint_detail/export"
        Then the response status should be 200
        Then I do not see an option "ECF2" in form field "[filter][eventCarbonFootprintId]"
        When I pick "ECF2" in the autocomplete "eventCarbonFootprintId"
        When I submit a form with "[filter][eventCarbonFootprintId]:ECF2" and button "Exportar"
        Then the response status should be 200
        Then I do not see a text "La exportación se está generando"

    Scenario: As a normal user I cannot access the event carbon footprint detail export
        Given account "Mi cuenta" with id "666" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "666" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/event_carbon_footprint_detail/export"
        Then the response status should be 403

    Scenario: The wizard offers the export only for a completed footprint
        Given account "Mi cuenta" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "ECF1" for event "333" of account "666" completed exists
        Given event "Evento B" with id "444" of account "666" exists
        Given event carbon footprint with id "ECF2" for event "444" of account "666" with materials answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/results"
        Then the response status should be 200
        Then I see a link to "/es/event_carbon_footprint_detail/export?eventCarbonFootprintId=ECF1"
        When I go to "/es/events/444/carbon-footprint/step/assembly"
        Then the response status should be 200
        Then I do not see a link with name "Exportar"
