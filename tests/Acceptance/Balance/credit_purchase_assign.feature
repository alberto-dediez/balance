Feature:
    Ability for admins to assign CO2 credits directly to an account

    Background:
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given project "Item 1" with id "1111" exists

    Scenario: As an admin I can assign credits to an account
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits"
        Then I see a text "Asignar créditos"
        When I go to "/es/admin/credits/register"
        Then the response status should be 200
        When I submit a form with "accountId:666|projectId:1111|purchasedTonnes:25|paymentType:paid_by_bioscore|reference:ASSIGN-1" and button "Asignar créditos"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Créditos asignados correctamente"
        Then I see a text "ASSIGN-1"
        Then I see a text "Asignado por el administrador"
        Then I do not see a text "Parcial"
        Then project "1111" should be adhered to account "666"
        Then account "666" should have "25" available tonnes in project "1111"

    Scenario: The assigned credits are available to the owner
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/register"
        When I submit a form with "accountId:666|projectId:1111|purchasedTonnes:25|paymentType:paid_by_bioscore|reference:ASSIGN-1" and button "Asignar créditos"
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits"
        Then the response status should be 200
        Then I see a text "ASSIGN-1"
        Then I see a text "25,0000"

    Scenario: The assigned credit is labelled as assigned in its detail
        Given project "1111" is adhered to account "666"
        Given credit purchase "3333" assigned to account "666" and project "1111" with "25" tonnes and reference "ASSIGN-2" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/3333"
        Then the response status should be 200
        Then I see a text "Toneladas asignadas"
        Then I see a text "Fecha de asignación"
        Then I do not see a text "Toneladas solicitadas"

    Scenario: Assigning credits validates the pending request of the account
        Given credit purchase "2222" for account "666" and project "1111" requested by "owner@dediez.software" with "10" tonnes exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/register"
        When I submit a form with "accountId:666|projectId:1111|purchasedTonnes:25|paymentType:paid_by_bioscore|reference:ASSIGN-3" and button "Asignar créditos"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Créditos asignados: se ha validado la solicitud pendiente de la cuenta"
        When I go to "/es/admin/credits/2222"
        Then the response status should be 200
        Then I see a text "ASSIGN-3"
        Then I see a text "Validada"
        Then credit purchase "2222" should have "25" free tonnes
        Then account "666" should have "25" available tonnes in project "1111"

    Scenario: Assigning credits warns about the remaining pending requests of the account
        Given credit purchase "2222" for account "666" and project "1111" requested by "owner@dediez.software" with "10" tonnes exists
        Given credit purchase "2223" for account "666" and project "1111" requested by "owner@dediez.software" with "5" tonnes exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/register"
        When I submit a form with "accountId:666|projectId:1111|purchasedTonnes:10|paymentType:paid_by_bioscore|reference:ASSIGN-9" and button "Asignar créditos"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "La cuenta mantiene 1 solicitud pendiente para este proyecto"

    Scenario: Assigning fewer tonnes than requested validates the pending request partially
        Given credit purchase "2222" for account "666" and project "1111" requested by "owner@dediez.software" with "10" tonnes exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/register"
        When I submit a form with "accountId:666|projectId:1111|purchasedTonnes:6|paymentType:paid_by_bioscore|reference:ASSIGN-4" and button "Asignar créditos"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Créditos asignados: se ha validado parcialmente la solicitud pendiente de la cuenta"

    Scenario: Validating a pending request through assignment notifies the requester
        Given credit purchase "2222" for account "666" and project "1111" requested by "owner@dediez.software" with "10" tonnes exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/register"
        When I submit a form with "accountId:666|projectId:1111|purchasedTonnes:10|paymentType:paid_by_bioscore|reference:ASSIGN-5" and button "Asignar créditos"
        When the queued messages are processed
        When I am logged as "owner@dediez.software"
        When I go to "/es/notifications"
        Then the response status should be 200
        Then I see a text "Su compra de créditos ha sido validada"

    Scenario: Assigning new credits notifies the owners of the account
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/register"
        When I submit a form with "accountId:666|projectId:1111|purchasedTonnes:25|paymentType:paid_by_bioscore|reference:ASSIGN-6" and button "Asignar créditos"
        When the queued messages are processed
        When I am logged as "owner@dediez.software"
        When I go to "/es/notifications"
        Then the response status should be 200
        Then I see a text "Se han asignado créditos de CO2 a su cuenta"

    Scenario: Assigning credits does not notify the administrators of a new request
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/register"
        When I submit a form with "accountId:666|projectId:1111|purchasedTonnes:25|paymentType:paid_by_bioscore|reference:ASSIGN-7" and button "Asignar créditos"
        When the queued messages are processed
        When I go to "/es/notifications"
        Then the response status should be 200
        Then I do not see a text "Nueva solicitud de créditos de CO2"

    Scenario: A duplicated reference is rejected
        Given account "OtherCompany" with id "777" exists
        Given credit purchase "2222" for account "777" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-1"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/register"
        When I submit a form with "accountId:666|projectId:1111|purchasedTonnes:25|paymentType:paid_by_bioscore|reference:REF-1" and button "Asignar créditos"
        Then the response status should be 200
        Then I see a text "El identificador REF-1 ya está registrado en otra compra."
        Then account "666" should have "0" available tonnes in project "1111"

    Scenario: An account without the compensation feature cannot receive credits
        Given account "OtherCompany" with id "777" exists
        Given account "666" has feature "compensation" disabled
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/register"
        Then the response status should be 200
        Then I see an option "OtherCompany" in form field "accountId"
        Then I do not see an option "MyCompany" in form field "accountId"

    Scenario: An assigned credit paid by Bioscore can be transferred
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/register"
        When I submit a form with "accountId:666|projectId:1111|purchasedTonnes:25|paymentType:paid_by_bioscore|reference:ASSIGN-8" and button "Asignar créditos"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Transferir crédito"

    Scenario: As an owner I can't assign credits
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/credits/register"
        Then the response status should be 403

    Scenario: The received lots of existing transfers are labelled as received
        Given user "payment" with role "ROLE_ADMIN" exists
        Given account "FourthCompany" with id "777" exists
        Given european union country "España" with iso "ESP" and vat "21" and id "C-ES" exists
        Given project "1111" is adhered to account "666"
        Given credit purchase "2222" for account "666" and project "1111" requesting "100" tonnes exists
        Given credit purchase "2222" is validated with "100" tonnes and reference "REF-T" paid by "paid_by_bioscore"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/transfer"
        When I submit a form with "destinationAccountIds:[777]" and button "Transferir crédito"
        When I follow redirect
        Then the response status should be 200
        When I go to "/es/admin/credits"
        Then I see a text "Recibido por transferencia"

    Scenario: As an admin I can filter the credit purchases by origin
        Given project "1111" is adhered to account "666"
        Given credit purchase "3333" assigned to account "666" and project "1111" with "25" tonnes and reference "ASSIGN-10" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        Given credit purchase "2222" is validated with "10" tonnes and reference "REF-ORIGIN"
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits"
        When I submit a form with "origin:assigned" and button "list_credit_purchases[filter]"
        Then the response status should be 200
        Then I see a text "ASSIGN-10"
        Then I do not see a text "REF-ORIGIN"
