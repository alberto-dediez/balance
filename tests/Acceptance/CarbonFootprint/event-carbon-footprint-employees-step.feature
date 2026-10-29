Feature:
    I want to go through the employees step of an event carbon footprint

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given employee "Ana" with email "ana@example.com" and id "E1" of account "666" exists
        Given employee "Luis" with email "luis@example.com" and id "E2" of account "666" exists

    Scenario: A total below the selected employees is refused with a translated message
        Given event carbon footprint with id "999" for event "333" of account "666" with providers answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/en/events/333/carbon-footprint/step/employees"
        When I submit the employees step form with button "Next", employees "E1,E2" and total "1"
        Then I am redirected to route "events_carbon_footprint_employees"
        Then I see a text "The total number of employees cannot be lower than the number of selected employees"
