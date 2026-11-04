Feature:
    As an admin I want to list, filter and remove the distributor assignments

    Background:
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        Given user "owner@greenhost.test" with role "ROLE_OWNER" of account "555" exists
        Given account "Otra Red" with id "666" exists
        Given account "666" is a distributor
        Given account "Hotel A" with id "1111" exists
        Given account "Hotel B" with id "2222" exists
        Given property "Hotel Rio" with id "21" of account "2222" exists
        Given distributor "555" is assigned to account "1111"
        Given distributor "555" is assigned to property "21"
        Given distributor "666" is assigned to account "2222"

    Scenario: As an admin, I see every assignment with its scope
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/distributor-assignments"
        Then the response status should be 200
        Then I see a table with 5 rows
        Then I see a text "Toda la cuenta"
        Then I see a text "Hotel Rio"

    Scenario: As an admin, I filter the assignments by distributor
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/distributor-assignments"
        When I submit a form with "distributorAccountId:666" and button "list_distributor_assignments[filter]"
        Then the response status should be 200
        Then I see a table with 3 rows

    Scenario: As an admin, I filter the assignments by adhered account
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/distributor-assignments"
        When I submit a form with "accountId:1111" and button "list_distributor_assignments[filter]"
        Then the response status should be 200
        Then I see a table with 3 rows

    Scenario: As an admin, I filter by adhered account and see the assignments of its properties
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/distributor-assignments"
        When I submit a form with "accountId:2222" and button "list_distributor_assignments[filter]"
        Then the response status should be 200
        Then I see a table with 4 rows
        Then I see a text "Hotel Rio"

    Scenario: As an admin, I see an empty state when there are no assignments
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/distributor-assignments"
        When I submit a form with "accountId:666" and button "list_distributor_assignments[filter]"
        Then the response status should be 200
        Then I see a text "No hay asignaciones de distribuidor."

    Scenario: Removing an adhered account removes the assignments of its properties
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/accounts/remove/2222" with csrf token "remove"
        When I follow redirect
        When I go to "/es/admin/distributor-assignments"
        Then the response status should be 200
        Then I see a table with 3 rows
        Then I do not see a text "Hotel Rio"

    Scenario: As an admin, I remove an assignment without notifying the distributor
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/distributor-assignments"
        When I submit a form with "accountId:1111" and button "list_distributor_assignments[filter]"
        When I click on the action "Eliminar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha retirado el distribuidor Greenhost"
        Then user "owner@greenhost.test" has 0 notifications with subject "Se ha retirado tu acceso como distribuidor"

    Scenario: As an owner, I can not access the admin list of assignments
        When I am logged as "owner@greenhost.test"
        When I go to "/es/admin/distributor-assignments"
        Then the response status should be 403
