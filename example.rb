=begin
https://sugar.profilingsolutions.com/sugar
http://sugar.profilingsolutions.com/sugar
https://psi.sugarondemand.com
http://10.0.0.5/svsi/
=end

require 'SugarCRM'

username = 'admin'
password = 'admin'
url = 'http://10.0.0.5/svsi'

password = Digest::MD5.hexdigest(password)
sugar = SugarCRM.new(url,username,password)
