Feature:
    I want to assign a distributor to several properties at once from the admin properties list

    Scenario: As an admin, I can assign a distributor to the selected properties
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Hotel A" with id "1111" exists
        Given property "Hotel Playa" with id "11" of account "1111" exists
        Given property "Hotel Sierra" with id "12" of account "1111" exists
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/properties"
        When I submit a form with "elements:[11,12]|action:assign-distributor|distributorAccountId:555" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha asignado el distribuidor seleccionado a la propiedad"
        When I go to "/es/admin/distributor-assignments"
        Then I see a text "Hotel Playa"
        Then I see a text "Hotel Sierra"
        Then I do not see a text "Toda la cuenta"

    Scenario: As an admin, I cannot assign a distributor to its own properties
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        Given property "Sede Greenhost" with id "50" of account "555" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/properties"
        When I submit a form with "elements:[50]|action:assign-distributor|distributorAccountId:555" and button "Realizar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "No se ha podido asignar el distribuidor seleccionado a la propiedad"
