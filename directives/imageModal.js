angular.module('app').controller('ImageModalController', function ($uibModalInstance, parent, image, imageUrl)
{
    var m = this;
    m.image = image;
    m.imageUrl = imageUrl;
    m.parent = parent;
    m.title = parent.imageTitle(image);
    m.caption = parent.imageDescription(image);
    
    m.ok = function()
    {
        $uibModalInstance.close(m.imageUrl);
    };

    m.cancel = function()
    {
        $uibModalInstance.dismiss('cancel');
    };

    m.getDomainName = function(url)
    {
        if(!url) return "";
        return url.substringAfter("//").substringBefore("/");
    }

    m.domain = m.getDomainName(image.link);

});